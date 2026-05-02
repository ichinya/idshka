FROM node:24-alpine

RUN apk add --no-cache openssl

WORKDIR /app

COPY examples/idshka-web-client/package*.json ./
RUN npm ci --omit=dev

COPY examples/idshka-web-client/src ./src
COPY examples/idshka-web-client/docker-entrypoint.sh /usr/local/bin/idshka-web-client-entrypoint
RUN chmod +x /usr/local/bin/idshka-web-client-entrypoint

EXPOSE 8443

ENTRYPOINT ["idshka-web-client-entrypoint"]
CMD ["npm", "start"]
