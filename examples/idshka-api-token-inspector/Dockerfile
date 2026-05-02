FROM node:24-alpine

RUN apk add --no-cache openssl

WORKDIR /app

COPY examples/idshka-api-token-inspector/package*.json ./
RUN npm ci --omit=dev

COPY examples/idshka-api-token-inspector/src ./src
COPY examples/idshka-api-token-inspector/docker-entrypoint.sh /usr/local/bin/idshka-api-token-inspector-entrypoint
RUN chmod +x /usr/local/bin/idshka-api-token-inspector-entrypoint

EXPOSE 8444

ENTRYPOINT ["idshka-api-token-inspector-entrypoint"]
CMD ["npm", "start"]
