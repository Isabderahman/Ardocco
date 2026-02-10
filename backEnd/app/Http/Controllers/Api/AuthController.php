<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Contract;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Account status flow for vendeur/acheteur:
     * 1. pending_contract - Account created, waiting for contract signature
     * 2. pending_approval - Contract signed, waiting for admin approval
     * 3. active - Approved by admin, full access
     * 4. rejected - Rejected by admin
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:acheteur,vendeur,agent,expert,promoteur',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'cin' => 'nullable|string|max:20',
            'device_name' => 'nullable|string|max:255',
        ]);

        // Roles that require contract signing and admin approval
        $requiresApproval = in_array($validated['role'], ['acheteur', 'vendeur', 'promoteur']);

        $user = User::create([
            'email' => strtolower($validated['email']),
            'password' => $validated['password'],
            'role' => $validated['role'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'cin' => $validated['cin'] ?? null,
            'is_verified' => false,
            'is_active' => !$requiresApproval, // Only active immediately if agent/expert
            'account_status' => $requiresApproval ? 'pending_contract' : 'active',
            'contract_token' => $requiresApproval ? Str::random(64) : null,
        ]);

        // If requires approval, create contract and send email
        if ($requiresApproval) {
            $contractType = match ($validated['role']) {
                'vendeur' => 'vendeur_agreement',
                'promoteur' => 'promoteur_agreement',
                default => 'acheteur_agreement',
            };

            $contract = Contract::create([
                'user_id' => $user->id,
                'contract_type' => $contractType,
                'status' => 'pending',
                'terms' => $this->getContractTerms($validated['role']),
            ]);

            // Send contract email
            $this->sendContractEmail($user, $contract);

            // Notify admins
            $this->notifyAdminsNewRegistration($user);

            return response()->json([
                'success' => true,
                'message' => 'Compte créé. Veuillez vérifier votre email pour signer le contrat.',
                'account_status' => 'pending_contract',
                'user' => $user->only(['id', 'email', 'first_name', 'last_name', 'role', 'account_status']),
            ], 201);
        }

        // For agent/expert, create token immediately
        $tokenName = $validated['device_name'] ?? ($request->userAgent() ?: 'api');
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Compte créé avec succès.',
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = User::query()
            ->where('email', strtolower($validated['email']))
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides.',
            ], 401);
        }

        // Check account status
        if ($user->account_status === 'pending_contract') {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez signer le contrat envoyé par email avant de vous connecter.',
                'account_status' => 'pending_contract',
            ], 403);
        }

        if ($user->account_status === 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est en attente d\'approbation par un administrateur.',
                'account_status' => 'pending_approval',
            ], 403);
        }

        if ($user->account_status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été rejeté. Contactez le support pour plus d\'informations.',
                'account_status' => 'rejected',
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est désactivé.',
            ], 403);
        }

        $user->forceFill(['last_login' => now()])->save();

        $tokenName = $validated['device_name'] ?? ($request->userAgent() ?: 'api');
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie.',
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Sign contract via token from email
     */
    public function signContract(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|size:64',
            'signature' => 'required|string|max:255',
            'accept_terms' => 'required|accepted',
        ]);

        $user = User::where('contract_token', $validated['token'])
            ->where('account_status', 'pending_contract')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Lien invalide ou expiré.',
            ], 404);
        }

        // Update contract
        $contract = Contract::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($contract) {
            $contract->update([
                'status' => 'signed',
                'signed_at' => now(),
                'signature' => $validated['signature'],
            ]);
        }

        // Update user status
        $user->update([
            'account_status' => 'pending_approval',
            'contract_signed_at' => now(),
            'contract_token' => null, // Invalidate token
        ]);

        // Notify admins
        $this->notifyAdminsContractSigned($user);

        return response()->json([
            'success' => true,
            'message' => 'Contrat signé avec succès. Votre compte est en attente d\'approbation.',
            'account_status' => 'pending_approval',
        ]);
    }

    /**
     * Admin: Approve user account
     */
    public function approveAccount(Request $request, User $user)
    {
        $admin = $request->user();

        if (!$admin->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        if ($user->account_status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Ce compte n\'est pas en attente d\'approbation.',
            ], 400);
        }

        $user->update([
            'account_status' => 'active',
            'is_active' => true,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        // Notify user
        Notification::create([
            'user_id' => $user->id,
            'type' => 'account_approved',
            'title' => 'Compte approuvé',
            'message' => 'Votre compte a été approuvé. Vous pouvez maintenant vous connecter.',
            'link' => '/login',
        ]);

        // Send approval email
        $this->sendApprovalEmail($user);

        return response()->json([
            'success' => true,
            'message' => 'Compte approuvé avec succès.',
            'user' => $user,
        ]);
    }

    /**
     * Admin: Reject user account
     */
    public function rejectAccount(Request $request, User $user)
    {
        $admin = $request->user();

        if (!$admin->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user->update([
            'account_status' => 'rejected',
            'is_active' => false,
            'rejection_reason' => $validated['reason'],
            'rejected_by' => $admin->id,
            'rejected_at' => now(),
        ]);

        // Notify user
        Notification::create([
            'user_id' => $user->id,
            'type' => 'account_rejected',
            'title' => 'Compte rejeté',
            'message' => "Votre compte a été rejeté. Raison: {$validated['reason']}",
        ]);

        // Send rejection email
        $this->sendRejectionEmail($user, $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => 'Compte rejeté.',
            'user' => $user,
        ]);
    }

    /**
     * Resend contract email
     */
    public function resendContract(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])
            ->where('account_status', 'pending_contract')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun compte en attente de signature pour cet email.',
            ], 404);
        }

        // Generate new token
        $user->update(['contract_token' => Str::random(64)]);

        $contract = Contract::where('user_id', $user->id)->first();
        $this->sendContractEmail($user, $contract);

        return response()->json([
            'success' => true,
            'message' => 'Email de contrat renvoyé.',
        ]);
    }

    private function getContractTerms(string $role): array
    {
        $baseTerms = [
            'platform_name' => 'ARDOCCO',
            'version' => '1.0',
            'created_at' => now()->toDateTimeString(),
        ];

        if ($role === 'vendeur') {
            return array_merge($baseTerms, [
                'type' => 'vendeur_agreement',
                'commission_rate' => 5,
                'listing_rules' => [
                    'Toutes les annonces doivent être approuvées par un administrateur',
                    'Les informations fournies doivent être exactes et vérifiables',
                    'Les documents cadastraux doivent être à jour',
                ],
            ]);
        }

        if ($role === 'promoteur') {
            return array_merge($baseTerms, [
                'type' => 'promoteur_agreement',
                'terms' => [
                    'Accès aux informations détaillées des terrains disponibles',
                    'Possibilité de demander l\'accès aux annonces des vendeurs',
                    'Engagement de confidentialité des données',
                    'Respect des délais et procédures d\'investissement',
                ],
            ]);
        }

        return array_merge($baseTerms, [
            'type' => 'acheteur_agreement',
            'terms' => [
                'Accès aux informations détaillées des terrains',
                'Contact direct avec les agents et vendeurs',
                'Engagement de confidentialité des données',
            ],
        ]);
    }

    private function sendContractEmail(User $user, ?Contract $contract): void
    {
        // TODO: Implement actual email sending with Mail facade
        // For now, log the contract URL
        $contractUrl = config('app.frontend_url') . '/sign-contract?token=' . $user->contract_token;

        \Log::info('Contract email would be sent to: ' . $user->email, [
            'contract_url' => $contractUrl,
            'user_id' => $user->id,
            'role' => $user->role,
        ]);
    }

    private function sendApprovalEmail(User $user): void
    {
        \Log::info('Approval email would be sent to: ' . $user->email);
    }

    private function sendRejectionEmail(User $user, string $reason): void
    {
        \Log::info('Rejection email would be sent to: ' . $user->email, ['reason' => $reason]);
    }

    private function notifyAdminsNewRegistration(User $user): void
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'new_registration',
                'title' => 'Nouvelle inscription',
                'message' => "{$user->first_name} {$user->last_name} ({$user->role}) s'est inscrit.",
                'link' => '/admin/users/' . $user->id,
            ]);
        }
    }

    private function notifyAdminsContractSigned(User $user): void
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'contract_signed',
                'title' => 'Contrat signé',
                'message' => "{$user->first_name} {$user->last_name} a signé son contrat. En attente d'approbation.",
                'link' => '/admin/users/' . $user->id,
            ]);
        }
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }

    /**
     * Update authenticated user's profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'cin' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès.',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Update authenticated user's password
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe actuel est incorrect.',
            ], 422);
        }

        $user->update(['password' => $validated['password']]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe mis à jour avec succès.',
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out',
        ]);
    }
}
