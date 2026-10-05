# Preserve PostgreSQL 18 and the existing Alpine-based data volume.
FROM postgres:18.6-alpine AS vector-build
ARG PGVECTOR_VERSION=0.8.7
RUN apk add --no-cache build-base linux-headers \
    && wget -qO /tmp/pgvector.tar.gz "https://github.com/pgvector/pgvector/archive/refs/tags/v${PGVECTOR_VERSION}.tar.gz" \
    && mkdir /tmp/pgvector \
    && tar -xzf /tmp/pgvector.tar.gz -C /tmp/pgvector --strip-components=1 \
    && make -C /tmp/pgvector OPTFLAGS="" with_llvm=no \
    && make -C /tmp/pgvector OPTFLAGS="" with_llvm=no install

FROM postgres:18.6-alpine
COPY --from=vector-build /usr/local/lib/postgresql/vector.so /usr/local/lib/postgresql/vector.so
COPY --from=vector-build /usr/local/share/postgresql/extension/vector* /usr/local/share/postgresql/extension/
COPY --from=vector-build /tmp/pgvector/LICENSE /usr/share/doc/pgvector/LICENSE
