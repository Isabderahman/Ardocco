FROM node:20-alpine

WORKDIR /app

# Install dependencies for sharp (image processing)
RUN apk add --no-cache vips-dev build-base python3

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci --only=production

# Copy application code
COPY . .

ENV NODE_ENV=production
ENV AI_PORT=8001

EXPOSE 8001

CMD ["node", "server.js"]
