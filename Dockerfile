FROM serversideup/php:8.2-fpm-nginx

USER root

# Install system dependencies, Node.js 22.x, and utilities for Composer
RUN apt-get update && apt-get install -y gnupg curl ca-certificates unzip git && \
    # Install Docker CLI and Compose
    install -m 0755 -d /etc/apt/keyrings && \
    curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc && \
    chmod a+r /etc/apt/keyrings/docker.asc && \
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/debian bookworm stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null && \
    apt-get update && \
    apt-get install -y docker-ce-cli docker-compose-plugin || apt-get install -y docker.io docker-buildx || true && \
    # Install Node.js
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && \
    apt-get install -y nodejs && \
    # Install PHP extensions
    curl -sSLf \
        -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions imap gmp soap intl bcmath gd redis && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Configure Docker socket access for www-data user
RUN mkdir -p /etc/entrypoint.d && \
    printf "#!/bin/sh\n\
if [ -S /var/run/docker.sock ]; then\n\
    SOCK_GID=\$(stat -c '%%g' /var/run/docker.sock)\n\
    echo \"Fixing docker socket permissions (GID: \$SOCK_GID)...\"\n\
    if getent group \$SOCK_GID; then\n\
        GROUP_NAME=\$(getent group \$SOCK_GID | cut -d: -f1)\n\
        usermod -aG \$GROUP_NAME www-data\n\
    else\n\
        groupadd -g \$SOCK_GID docker_sock_runtime\n\
        usermod -aG docker_sock_runtime www-data\n\
    fi\n\
fi\n" > /etc/entrypoint.d/99-fix-docker-sock.sh && \
    chmod +x /etc/entrypoint.d/99-fix-docker-sock.sh
