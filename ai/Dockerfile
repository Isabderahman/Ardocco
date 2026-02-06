FROM node:20-alpine

WORKDIR /app

COPY server.js /app/server.js

ENV NODE_ENV=production
ENV AI_PORT=8001

EXPOSE 8001

CMD ["node", "server.js"]

