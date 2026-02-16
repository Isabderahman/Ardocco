require('dotenv').config()

const express = require('express')
const multer = require('multer')
const cors = require('cors')
const OpenAI = require('openai')
const fs = require('fs')
const path = require('path')
const pdfParse = require('pdf-parse')
const sharp = require('sharp')
const PDFDocument = require('pdfkit')

const app = express()
const port = Number.parseInt(process.env.AI_PORT || process.env.PORT || '8003', 10)
const host = '0.0.0.0'

// Initialize OpenAI
const openai = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY,
})

// Middleware
app.use(cors())
app.use(express.json({ limit: '50mb' }))
app.use(express.urlencoded({ extended: true, limit: '50mb' }))

// AI Secret Key Authentication Middleware
// Only applied to /api routes (except health check)
const AI_SECRET_KEY = process.env.AI_SECRET_KEY

const validateSecretKey = (req, res, next) => {
  // Skip validation if no secret key is configured (development mode)
  if (!AI_SECRET_KEY) {
    return next()
  }

  const providedKey = req.header('X-AI-Secret-Key')

  if (!providedKey) {
    return res.status(401).json({
      error: 'Unauthorized',
      message: 'Missing X-AI-Secret-Key header',
    })
  }

  if (providedKey !== AI_SECRET_KEY) {
    return res.status(403).json({
      error: 'Forbidden',
      message: 'Invalid API secret key',
    })
  }

  next()
}

// Apply secret key validation to all /api routes
app.use('/api', validateSecretKey)

// Configure multer for file uploads
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    const uploadDir = '/tmp/ardocco-uploads'
    if (!fs.existsSync(uploadDir)) {
      fs.mkdirSync(uploadDir, { recursive: true })
    }
    cb(null, uploadDir)
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1e9)
    cb(null, uniqueSuffix + '-' + file.originalname)
  },
})

const upload = multer({
  storage,
  limits: { fileSize: 50 * 1024 * 1024 }, // 50MB limit
  fileFilter: (req, file, cb) => {
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp']
    if (allowedTypes.includes(file.mimetype)) {
      cb(null, true)
    } else {
      cb(new Error('Invalid file type. Only PDF and images are allowed.'))
    }
  },
})

// Helper: Convert image to base64
async function imageToBase64(filePath) {
  const buffer = await sharp(filePath)
    .resize(2048, 2048, { fit: 'inside', withoutEnlargement: true })
    .jpeg({ quality: 85 })
    .toBuffer()
  return buffer.toString('base64')
}

// Helper: Extract text from PDF
async function extractPdfText(filePath) {
  const dataBuffer = fs.readFileSync(filePath)
  const data = await pdfParse(dataBuffer)
  return data.text
}

// Helper: Clean up uploaded file
function cleanupFile(filePath) {
  if (filePath && fs.existsSync(filePath)) {
    fs.unlinkSync(filePath)
  }
}

// Health check endpoint
app.get('/healthz', (req, res) => {
  res.json({ ok: true, service: 'ardocco-ai' })
})

// Root endpoint
app.get('/', (req, res) => {
  res.json({
    name: 'ARDOCCO AI Service',
    version: '1.0.0',
    endpoints: {
      '/healthz': 'Health check',
      '/api/process/plan-cadastral': 'Process cadastral plan (POST with file)',
      '/api/process/titre-foncier': 'Process land title document (POST with file)',
      '/api/process/document': 'Process any document (POST with file)',
      '/api/estimate-price': 'Estimate land price based on data (POST)',
      '/api/analyze-terrain': 'Analyze terrain characteristics (POST)',
      '/api/analyze-plans': 'Analyze architectural plans for investment study (POST with files)',
      '/api/investment-study/suggest': 'Get AI suggestions for investment study (POST)',
      '/api/generate-pdf/business-plan': 'Generate business plan PDF (POST)',
    },
  })
})

/**
 * Process Plan Cadastral (Cadastral Plan)
 * Extracts: boundaries, superficie, coordinates, parcel numbers
 */
app.post('/api/process/plan-cadastral', upload.single('file'), async (req, res) => {
  const filePath = req.file?.path

  try {
    if (!req.file) {
      return res.status(400).json({ error: 'No file uploaded' })
    }

    let content = []

    if (req.file.mimetype === 'application/pdf') {
      const text = await extractPdfText(filePath)
      content = [
        {
          type: 'text',
          text: `Extracted text from cadastral plan PDF:\n\n${text}`,
        },
      ]
    } else {
      const base64Image = await imageToBase64(filePath)
      content = [
        {
          type: 'image_url',
          image_url: {
            url: `data:image/jpeg;base64,${base64Image}`,
          },
        },
      ]
    }

    const response = await openai.chat.completions.create({
      model: 'gpt-4o',
      messages: [
        {
          role: 'system',
          content: `Tu es un expert en analyse de plans cadastraux marocains. Analyse le document fourni et extrait les informations suivantes de manière structurée.

Réponds UNIQUEMENT avec un objet JSON valide contenant ces champs:
{
  "numero_titre": "Numéro du titre foncier si visible",
  "numero_parcelle": "Numéro de parcelle",
  "superficie_m2": "Superficie en m² (nombre uniquement)",
  "commune": "Nom de la commune",
  "arrondissement": "Arrondissement si disponible",
  "quartier": "Quartier ou lieu-dit",
  "coordonnees": {
    "lambert": "Coordonnées Lambert si disponibles",
    "points": ["Liste des points de délimitation"]
  },
  "limites": {
    "nord": "Description limite nord",
    "sud": "Description limite sud",
    "est": "Description limite est",
    "ouest": "Description limite ouest"
  },
  "forme": "rectangulaire|carree|irreguliere|triangulaire|polygonale",
  "perimetre_m": "Périmètre en mètres si calculable",
  "observations": "Toute information supplémentaire pertinente",
  "confiance": "haute|moyenne|basse"
}

Si une information n'est pas disponible, utilise null.`,
        },
        {
          role: 'user',
          content: content,
        },
      ],
      max_tokens: 2000,
      temperature: 0.1,
    })

    const aiResponse = response.choices[0]?.message?.content || '{}'

    // Parse JSON from response
    let extractedData
    try {
      const jsonMatch = aiResponse.match(/\{[\s\S]*\}/)
      extractedData = jsonMatch ? JSON.parse(jsonMatch[0]) : {}
    } catch {
      extractedData = { raw_response: aiResponse, parse_error: true }
    }

    res.json({
      success: true,
      document_type: 'plan_cadastral',
      extracted_data: extractedData,
      processing_time_ms: Date.now(),
    })
  } catch (error) {
    console.error('Error processing cadastral plan:', error)
    res.status(500).json({
      error: 'Failed to process cadastral plan',
      message: error.message,
    })
  } finally {
    cleanupFile(filePath)
  }
})

/**
 * Process Titre Foncier (Land Title)
 * Extracts: title number, owner info, property details, encumbrances
 */
app.post('/api/process/titre-foncier', upload.single('file'), async (req, res) => {
  const filePath = req.file?.path

  try {
    if (!req.file) {
      return res.status(400).json({ error: 'No file uploaded' })
    }

    let content = []

    if (req.file.mimetype === 'application/pdf') {
      const text = await extractPdfText(filePath)
      content = [
        {
          type: 'text',
          text: `Extracted text from land title document:\n\n${text}`,
        },
      ]
    } else {
      const base64Image = await imageToBase64(filePath)
      content = [
        {
          type: 'image_url',
          image_url: {
            url: `data:image/jpeg;base64,${base64Image}`,
          },
        },
      ]
    }

    const response = await openai.chat.completions.create({
      model: 'gpt-4o',
      messages: [
        {
          role: 'system',
          content: `Tu es un expert en analyse de titres fonciers marocains. Analyse le document fourni et extrait les informations suivantes de manière structurée.

Réponds UNIQUEMENT avec un objet JSON valide contenant ces champs:
{
  "numero_titre": "Numéro du titre foncier (format: TF XXXXX/XX)",
  "conservation_fonciere": "Nom de la conservation foncière",
  "date_creation": "Date de création du titre",
  "proprietaires": [
    {
      "nom": "Nom complet",
      "cin": "Numéro CIN si disponible",
      "part": "Quote-part (ex: 1/1, 1/2)",
      "type": "personne_physique|personne_morale"
    }
  ],
  "bien": {
    "nature": "Terrain nu|Terrain bâti|Immeuble|etc.",
    "superficie_m2": "Superficie en m²",
    "consistance": "Description de la consistance",
    "situation": "Adresse ou localisation",
    "commune": "Commune",
    "quartier": "Quartier"
  },
  "charges": [
    {
      "type": "Hypothèque|Servitude|Bail|etc.",
      "beneficiaire": "Nom du bénéficiaire",
      "date": "Date de l'inscription",
      "montant": "Montant si applicable"
    }
  ],
  "inscriptions_recentes": [
    {
      "numero": "Numéro d'inscription",
      "date": "Date",
      "nature": "Nature de l'opération"
    }
  ],
  "observations": "Observations importantes",
  "confiance": "haute|moyenne|basse"
}

Si une information n'est pas disponible, utilise null ou un tableau vide.`,
        },
        {
          role: 'user',
          content: content,
        },
      ],
      max_tokens: 3000,
      temperature: 0.1,
    })

    const aiResponse = response.choices[0]?.message?.content || '{}'

    let extractedData
    try {
      const jsonMatch = aiResponse.match(/\{[\s\S]*\}/)
      extractedData = jsonMatch ? JSON.parse(jsonMatch[0]) : {}
    } catch {
      extractedData = { raw_response: aiResponse, parse_error: true }
    }

    res.json({
      success: true,
      document_type: 'titre_foncier',
      extracted_data: extractedData,
      processing_time_ms: Date.now(),
    })
  } catch (error) {
    console.error('Error processing land title:', error)
    res.status(500).json({
      error: 'Failed to process land title',
      message: error.message,
    })
  } finally {
    cleanupFile(filePath)
  }
})

/**
 * Process any document (auto-detect type)
 */
app.post('/api/process/document', upload.single('file'), async (req, res) => {
  const filePath = req.file?.path
  const documentType = req.body.document_type || 'auto'

  try {
    if (!req.file) {
      return res.status(400).json({ error: 'No file uploaded' })
    }

    let content = []

    if (req.file.mimetype === 'application/pdf') {
      const text = await extractPdfText(filePath)
      content = [
        {
          type: 'text',
          text: `Document content:\n\n${text}`,
        },
      ]
    } else {
      const base64Image = await imageToBase64(filePath)
      content = [
        {
          type: 'image_url',
          image_url: {
            url: `data:image/jpeg;base64,${base64Image}`,
          },
        },
      ]
    }

    const response = await openai.chat.completions.create({
      model: 'gpt-4o',
      messages: [
        {
          role: 'system',
          content: `Tu es un expert en documents immobiliers marocains. Analyse le document fourni et:
1. Identifie le type de document (plan cadastral, titre foncier, certificat de propriété, attestation, note de renseignement, etc.)
2. Extrait toutes les informations pertinentes

Réponds UNIQUEMENT avec un objet JSON valide:
{
  "document_type": "Type de document identifié",
  "extracted_data": {
    // Toutes les informations extraites selon le type
  },
  "summary": "Résumé court du document",
  "confiance": "haute|moyenne|basse"
}`,
        },
        {
          role: 'user',
          content: content,
        },
      ],
      max_tokens: 3000,
      temperature: 0.1,
    })

    const aiResponse = response.choices[0]?.message?.content || '{}'

    let extractedData
    try {
      const jsonMatch = aiResponse.match(/\{[\s\S]*\}/)
      extractedData = jsonMatch ? JSON.parse(jsonMatch[0]) : {}
    } catch {
      extractedData = { raw_response: aiResponse, parse_error: true }
    }

    res.json({
      success: true,
      ...extractedData,
      processing_time_ms: Date.now(),
    })
  } catch (error) {
    console.error('Error processing document:', error)
    res.status(500).json({
      error: 'Failed to process document',
      message: error.message,
    })
  } finally {
    cleanupFile(filePath)
  }
})

/**
 * Estimate land price based on characteristics
 */
app.post('/api/estimate-price', async (req, res) => {
  try {
    const {
      superficie_m2,
      commune,
      quartier,
      type_terrain,
      zonage,
      viabilisation,
      coefficient_occupation,
      topographie,
    } = req.body

    if (!superficie_m2 || !commune) {
      return res.status(400).json({
        error: 'Missing required fields: superficie_m2 and commune are required',
      })
    }

    const response = await openai.chat.completions.create({
      model: 'gpt-4o',
      messages: [
        {
          role: 'system',
          content: `Tu es un expert en évaluation immobilière au Maroc, spécialisé dans les terrains de la région Casablanca-Settat.

En te basant sur les caractéristiques du terrain fournies et ta connaissance du marché immobilier marocain, estime le prix du terrain.

Réponds UNIQUEMENT avec un objet JSON valide:
{
  "prix_estime_total": "Prix total estimé en MAD",
  "prix_m2_min": "Prix minimum par m² en MAD",
  "prix_m2_max": "Prix maximum par m² en MAD",
  "prix_m2_moyen": "Prix moyen par m² en MAD",
  "facteurs_positifs": ["Liste des facteurs qui augmentent la valeur"],
  "facteurs_negatifs": ["Liste des facteurs qui diminuent la valeur"],
  "tendance_marche": "hausse|stable|baisse",
  "niveau_confiance": "haute|moyenne|basse",
  "commentaire": "Explication de l'estimation"
}`,
        },
        {
          role: 'user',
          content: `Estime le prix de ce terrain:
- Superficie: ${superficie_m2} m²
- Commune: ${commune}
- Quartier: ${quartier || 'Non spécifié'}
- Type de terrain: ${type_terrain || 'Non spécifié'}
- Zonage: ${zonage || 'Non spécifié'}
- Viabilisation: ${JSON.stringify(viabilisation) || 'Non spécifié'}
- Coefficient d'occupation: ${coefficient_occupation || 'Non spécifié'}
- Topographie: ${topographie || 'Non spécifié'}`,
        },
      ],
      max_tokens: 1500,
      temperature: 0.3,
    })

    const aiResponse = response.choices[0]?.message?.content || '{}'

    let estimation
    try {
      const jsonMatch = aiResponse.match(/\{[\s\S]*\}/)
      estimation = jsonMatch ? JSON.parse(jsonMatch[0]) : {}
    } catch {
      estimation = { raw_response: aiResponse, parse_error: true }
    }

    res.json({
      success: true,
      estimation,
    })
  } catch (error) {
    console.error('Error estimating price:', error)
    res.status(500).json({
      error: 'Failed to estimate price',
      message: error.message,
    })
  }
})

/**
 * Analyze terrain characteristics from description or images
 */
app.post('/api/analyze-terrain', upload.array('images', 10), async (req, res) => {
  const files = req.files || []

  try {
    const { description, latitude, longitude } = req.body

    const content = []

    // Add description if provided
    if (description) {
      content.push({
        type: 'text',
        text: `Description du terrain: ${description}`,
      })
    }

    // Add coordinates if provided
    if (latitude && longitude) {
      content.push({
        type: 'text',
        text: `Coordonnées GPS: ${latitude}, ${longitude}`,
      })
    }

    // Add images if provided
    for (const file of files) {
      if (file.mimetype.startsWith('image/')) {
        const base64Image = await imageToBase64(file.path)
        content.push({
          type: 'image_url',
          image_url: {
            url: `data:image/jpeg;base64,${base64Image}`,
          },
        })
      }
    }

    if (content.length === 0) {
      return res.status(400).json({
        error: 'Please provide description, images, or coordinates',
      })
    }

    const response = await openai.chat.completions.create({
      model: 'gpt-4o',
      messages: [
        {
          role: 'system',
          content: `Tu es un expert en analyse de terrains au Maroc. Analyse les informations fournies (description, images, localisation) et donne une évaluation détaillée.

Réponds UNIQUEMENT avec un objet JSON valide:
{
  "topographie": "plat|en_pente|valonne|accidente",
  "vegetation": "Description de la végétation visible",
  "acces": {
    "type": "route_goudronnee|piste|chemin",
    "qualite": "bonne|moyenne|difficile"
  },
  "environnement": {
    "description": "Description de l'environnement",
    "proximite_ville": "proche|moyen|eloigne",
    "nuisances": ["Liste des nuisances potentielles"]
  },
  "potentiel": {
    "usage_recommande": ["Liste des usages recommandés"],
    "points_forts": ["Points forts du terrain"],
    "points_faibles": ["Points faibles du terrain"]
  },
  "viabilisation_estimee": {
    "eau": "disponible|a_raccorder|difficile",
    "electricite": "disponible|a_raccorder|difficile",
    "assainissement": "disponible|fosse_septique|difficile"
  },
  "recommandations": ["Liste de recommandations"],
  "confiance": "haute|moyenne|basse"
}`,
        },
        {
          role: 'user',
          content: content,
        },
      ],
      max_tokens: 2000,
      temperature: 0.2,
    })

    const aiResponse = response.choices[0]?.message?.content || '{}'

    let analysis
    try {
      const jsonMatch = aiResponse.match(/\{[\s\S]*\}/)
      analysis = jsonMatch ? JSON.parse(jsonMatch[0]) : {}
    } catch {
      analysis = { raw_response: aiResponse, parse_error: true }
    }

    res.json({
      success: true,
      analysis,
    })
  } catch (error) {
    console.error('Error analyzing terrain:', error)
    res.status(500).json({
      error: 'Failed to analyze terrain',
      message: error.message,
    })
  } finally {
    // Cleanup all uploaded files
    for (const file of files) {
      cleanupFile(file.path)
    }
  }
})

/**
 * Analyze architectural plans for investment study
 * Extracts surfaces, floor counts, apartment layouts from plans
 */
app.post('/api/analyze-plans', upload.array('plans', 20), async (req, res) => {
  const files = req.files || []

  try {
    if (files.length === 0) {
      return res.status(400).json({ error: 'No plans uploaded' })
    }

    const content = []

    // Add any provided context
    if (req.body.context) {
      content.push({
        type: 'text',
        text: `Context: ${req.body.context}`,
      })
    }

    // Process all uploaded plans
    for (const file of files) {
      if (file.mimetype === 'application/pdf') {
        const text = await extractPdfText(file.path)
        content.push({
          type: 'text',
          text: `Plan PDF content (${file.originalname}):\n${text}`,
        })
      } else if (file.mimetype.startsWith('image/')) {
        const base64Image = await imageToBase64(file.path)
        content.push({
          type: 'image_url',
          image_url: {
            url: `data:image/jpeg;base64,${base64Image}`,
          },
        })
      }
    }

    const response = await openai.chat.completions.create({
      model: 'gpt-4o',
      messages: [
        {
          role: 'system',
          content: `Tu es un expert en analyse de plans architecturaux pour des projets immobiliers au Maroc. Analyse les plans fournis et extrait les informations pour une étude d'investissement.

Réponds UNIQUEMENT avec un objet JSON valide:
{
  "projet": {
    "type": "Type de projet (R+4, 1S/SOL R+4, etc.)",
    "nombre_sous_sols": "Nombre de sous-sols",
    "nombre_etages": "Nombre d'étages (hors sous-sol)",
    "has_mezzanine": true/false
  },
  "surfaces_par_niveau": {
    "sous_sol_1": "Surface en m² si applicable",
    "rdc": "Surface RDC en m²",
    "etage_1": "Surface étage 1 en m²",
    "etage_2": "Surface étage 2 en m²",
    "etage_3": "Surface étage 3 en m²",
    "etage_4": "Surface étage 4 en m²"
  },
  "surface_plancher_total": "Total surface plancher en m²",
  "surfaces_vendables": {
    "rdc": {
      "usage": "apparts|commerce|mixte",
      "surface": "Surface vendable en m²",
      "details": "Description des unités"
    },
    "mezzanine": {
      "usage": null,
      "surface": 0
    },
    "etages": {
      "usage": "apparts",
      "surface": "Surface vendable totale des étages en m²",
      "details": "Description des appartements par étage"
    }
  },
  "appartements": [
    {
      "niveau": "RDC|Etage 1|etc.",
      "type": "F2|F3|F4|Studio|etc.",
      "surface": "Surface en m²"
    }
  ],
  "parties_communes": {
    "escalier": "Surface en m²",
    "ascenseur": true/false,
    "hall": "Surface en m²",
    "parking": "Nombre de places"
  },
  "observations": "Notes importantes sur les plans",
  "confiance": "haute|moyenne|basse"
}

Si une information n'est pas visible ou calculable, utilise null.`,
        },
        {
          role: 'user',
          content: content,
        },
      ],
      max_tokens: 4000,
      temperature: 0.1,
    })

    const aiResponse = response.choices[0]?.message?.content || '{}'

    let analysis
    try {
      const jsonMatch = aiResponse.match(/\{[\s\S]*\}/)
      analysis = jsonMatch ? JSON.parse(jsonMatch[0]) : {}
    } catch {
      analysis = { raw_response: aiResponse, parse_error: true }
    }

    res.json({
      success: true,
      analysis,
    })
  } catch (error) {
    console.error('Error analyzing plans:', error)
    res.status(500).json({
      error: 'Failed to analyze plans',
      message: error.message,
    })
  } finally {
    for (const file of files) {
      cleanupFile(file.path)
    }
  }
})

/**
 * Generate investment study suggestions based on terrain data
 */
app.post('/api/investment-study/suggest', async (req, res) => {
  try {
    const {
      superficie_terrain,
      localisation,
      commune,
      quartier,
      zonage,
      coefficient_occupation,
      hauteur_max,
      prix_terrain_m2,
    } = req.body

    if (!superficie_terrain || !localisation) {
      return res.status(400).json({
        error: 'Missing required fields: superficie_terrain and localisation',
      })
    }

    const response = await openai.chat.completions.create({
      model: 'gpt-4o',
      messages: [
        {
          role: 'system',
          content: `Tu es un expert en promotion immobilière au Maroc. En te basant sur les caractéristiques du terrain, suggère les paramètres optimaux pour une étude d'investissement immobilier.

Réponds UNIQUEMENT avec un objet JSON valide:
{
  "type_projet_suggere": "R+3|R+4|R+5|1S/SOL R+4|etc.",
  "nombre_etages_recommande": "Nombre optimal d'étages",
  "nombre_sous_sols_recommande": "0 ou 1",
  "surface_plancher_estimee": "Surface totale estimée en m²",
  "surfaces_par_niveau": {
    "rdc": "Surface estimée RDC",
    "etage_courant": "Surface estimée par étage"
  },
  "repartition_suggeree": {
    "commerce_rdc": true/false,
    "pourcentage_commerce": "% surface commerce",
    "pourcentage_appart": "% surface appartements"
  },
  "couts_estimes": {
    "gros_oeuvres_m2": "Coût estimé gros œuvres/m²",
    "finition_m2": "Coût estimé finition/m²",
    "amenagement_divers": "Coût aménagements divers"
  },
  "prix_vente_estimes": {
    "m2_commerce": "Prix/m² commerce estimé",
    "m2_appart": "Prix/m² appartement estimé"
  },
  "ratio_rentabilite_estime": "% rentabilité estimée",
  "recommandations": ["Liste de recommandations"],
  "risques": ["Liste des risques potentiels"],
  "confiance": "haute|moyenne|basse"
}`,
        },
        {
          role: 'user',
          content: `Suggère les paramètres pour ce terrain:
- Superficie: ${superficie_terrain} m²
- Localisation: ${localisation}
- Commune: ${commune || 'Non spécifié'}
- Quartier: ${quartier || 'Non spécifié'}
- Zonage: ${zonage || 'Non spécifié'}
- Coefficient d'occupation: ${coefficient_occupation || 'Non spécifié'}
- Hauteur max autorisée: ${hauteur_max || 'Non spécifié'}
- Prix terrain/m²: ${prix_terrain_m2 || 'Non spécifié'} DHS`,
        },
      ],
      max_tokens: 2500,
      temperature: 0.3,
    })

    const aiResponse = response.choices[0]?.message?.content || '{}'

    let suggestions
    try {
      const jsonMatch = aiResponse.match(/\{[\s\S]*\}/)
      suggestions = jsonMatch ? JSON.parse(jsonMatch[0]) : {}
    } catch {
      suggestions = { raw_response: aiResponse, parse_error: true }
    }

    res.json({
      success: true,
      suggestions,
    })
  } catch (error) {
    console.error('Error generating suggestions:', error)
    res.status(500).json({
      error: 'Failed to generate suggestions',
      message: error.message,
    })
  }
})

/**
 * Generate Business Plan PDF
 */
app.post('/api/generate-pdf/business-plan', upload.array('plans', 10), async (req, res) => {
  const files = req.files || []

  try {
    const data = JSON.parse(req.body.data || '{}')

    if (!data.projet || !data.terrain) {
      return res.status(400).json({ error: 'Missing required data: projet and terrain' })
    }

    // Create PDF
    const doc = new PDFDocument({
      size: 'A4',
      margins: { top: 50, bottom: 50, left: 50, right: 50 },
    })

    // Set response headers
    res.setHeader('Content-Type', 'application/pdf')
    res.setHeader(
      'Content-Disposition',
      `attachment; filename="business-plan-${data.projet.titre || 'etude'}.pdf"`
    )

    doc.pipe(res)

    // Helper function for formatting numbers
    const formatNumber = (num) => {
      if (num === null || num === undefined) return '-'
      return new Intl.NumberFormat('fr-MA').format(num)
    }

    // Header
    doc.fontSize(24).font('Helvetica-Bold').text('Business Plan', { align: 'center' })
    doc.fontSize(16).font('Helvetica').text(`Projet ${data.projet.type || 'Immobilier'}`, { align: 'center' })
    doc.fontSize(12).text(`Sis : ${data.projet.localisation || '-'}`, { align: 'center' })
    doc.fontSize(12).text(`Prix du terrain : ${formatNumber(data.terrain.prix_m2)} DHS/m²`, { align: 'center' })
    doc.moveDown()
    doc.fontSize(10).text(`Version ${data.projet.version || new Date().toLocaleDateString('fr-FR')}`, { align: 'center' })

    doc.moveDown(2)

    // Coût d'investissement section
    doc.fontSize(14).font('Helvetica-Bold').text("Coût d'investissement", { underline: true })
    doc.moveDown(0.5)

    const investData = [
      ['DESIGNATION', 'SURFACE (m²)', 'MONTANT (DHS)'],
      ['Prix terrain', data.terrain.superficie_m2, formatNumber(data.terrain.prix_total)],
      ['Frais immatriculation', '-', formatNumber(data.terrain.frais_immatriculation)],
    ]

    // Construction surfaces
    if (data.construction?.surfaces_par_niveau) {
      doc.moveDown(0.5)
      doc.fontSize(11).font('Helvetica-Bold').text('Construction:')
      Object.entries(data.construction.surfaces_par_niveau).forEach(([niveau, surface]) => {
        doc.fontSize(10).font('Helvetica').text(`  ${niveau}: ${formatNumber(surface)} m²`)
      })
    }

    doc.moveDown(0.5)
    doc.fontSize(11).font('Helvetica-Bold').text(`TOTAL Surface Plancher: ${formatNumber(data.construction?.surface_plancher_total)} m²`)

    doc.moveDown()

    // Construction costs table
    doc.fontSize(11).font('Helvetica-Bold').text('Coûts de construction:')
    doc.fontSize(10).font('Helvetica')
    doc.text(`  Gros œuvres (DHS/m²): ${formatNumber(data.construction?.cout_gros_oeuvres_m2)}`)
    doc.text(`  Finition (DHS/m²): ${formatNumber(data.construction?.cout_finition_m2)}`)
    doc.text(`  Aménagement divers: ${formatNumber(data.construction?.amenagement_divers)} DHS`)
    doc.font('Helvetica-Bold').text(`  COUT Total Travaux TTC: ${formatNumber(data.construction?.cout_total_travaux)} DHS`)

    doc.moveDown(0.5)
    doc.fontSize(10).font('Helvetica')
    doc.text(`  Frais groupement études: ${formatNumber(data.frais?.groupement_etudes)} DHS`)
    doc.text(`  Frais Autorisation + Eclatement: ${formatNumber(data.frais?.autorisation_eclatement)} DHS`)
    doc.text(`  Frais Lydec: ${formatNumber(data.frais?.lydec)} DHS`)
    doc.font('Helvetica-Bold').text(`  Total Frais construction: ${formatNumber(data.frais?.total_construction)} DHS`)
    doc.moveDown(0.5)
    doc.fontSize(12).text(`  TOTAL INVESTISSEMENT: ${formatNumber(data.investissement?.total)} DHS`)

    doc.moveDown(2)

    // Chiffre d'affaires section
    doc.fontSize(14).font('Helvetica-Bold').text("Chiffre d'affaires", { underline: true })
    doc.moveDown(0.5)

    doc.fontSize(11).font('Helvetica-Bold').text('Surfaces vendables:')
    doc.fontSize(10).font('Helvetica')

    if (data.vente?.surfaces_vendables) {
      Object.entries(data.vente.surfaces_vendables).forEach(([niveau, info]) => {
        if (info && info.surface > 0) {
          doc.text(`  ${niveau}: ${info.usage || '-'} - ${formatNumber(info.surface)} m²`)
        }
      })
    }

    doc.moveDown(0.5)
    doc.font('Helvetica-Bold').text(`  Surface vendable Commerce: ${formatNumber(data.vente?.surface_commerce)} m²`)
    doc.text(`  Surface vendable Appart: ${formatNumber(data.vente?.surface_appart)} m²`)

    doc.moveDown()
    doc.fontSize(10).font('Helvetica')
    doc.text(`  Prix/m² appart: ${formatNumber(data.vente?.prix_m2_appart)} DHS`)
    doc.text(`  Prix/m² commerce: ${formatNumber(data.vente?.prix_m2_commerce)} DHS`)

    doc.moveDown()
    doc.font('Helvetica-Bold')
    doc.text(`  Revenus appart: ${formatNumber(data.resultat?.revenus_appart)} DHS`)
    doc.text(`  Revenus commerce: ${formatNumber(data.resultat?.revenus_commerce)} DHS`)
    doc.fontSize(11).text(`  Total revenues: ${formatNumber(data.resultat?.total_revenues)} DHS`)

    doc.moveDown()
    doc.fontSize(12).font('Helvetica-Bold')
    doc.text(`  Résultat Brut: ${formatNumber(data.resultat?.resultat_brute)} DHS`)
    doc.text(`  Ratio: ${data.resultat?.ratio?.toFixed(2) || '-'}%`)

    // Footer
    doc.moveDown(3)
    doc.fontSize(8).font('Helvetica').fillColor('gray')
    doc.text(`Généré par ARDOCCO AI - ${new Date().toLocaleDateString('fr-FR')}`, { align: 'center' })

    doc.end()
  } catch (error) {
    console.error('Error generating PDF:', error)
    res.status(500).json({
      error: 'Failed to generate PDF',
      message: error.message,
    })
  } finally {
    for (const file of files) {
      cleanupFile(file.path)
    }
  }
})

// Error handling middleware
app.use((err, req, res, next) => {
  console.error('Server error:', err)
  res.status(500).json({
    error: 'Internal server error',
    message: err.message,
  })
})

// Start server
app.listen(port, host, () => {
  console.log(`ARDOCCO AI service listening on http://${host}:${port}`)
  console.log('Available endpoints:')
  console.log('  POST /api/process/plan-cadastral    - Process cadastral plans')
  console.log('  POST /api/process/titre-foncier     - Process land titles')
  console.log('  POST /api/process/document          - Process any document')
  console.log('  POST /api/estimate-price            - Estimate land price')
  console.log('  POST /api/analyze-terrain           - Analyze terrain')
  console.log('  POST /api/analyze-plans             - Analyze architectural plans')
  console.log('  POST /api/investment-study/suggest  - AI investment suggestions')
  console.log('  POST /api/generate-pdf/business-plan - Generate business plan PDF')
})
