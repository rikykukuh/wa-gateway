FROM node:22-alpine

WORKDIR /app
COPY package*.json ./
RUN npm ci --omit=dev
COPY src ./src
RUN mkdir -p sessions

EXPOSE 3100
CMD ["npm", "start"]
