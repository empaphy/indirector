FROM ubuntu:bionic

ARG DEBIAN_FRONTEND=noninteractive
RUN --mount=type=cache,target=/var/cache/apt \
    --mount=type=cache,target=/var/lib/apt \
    --mount=type=tmpfs,target=/tmp \
    set -ex; \
    apt-get --yes update; \
    apt-get --yes install php7.2-cli php7.2-xdebug php7.2-zip unzip
