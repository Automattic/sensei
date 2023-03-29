FROM composer:2 AS composer
FROM php:7.4-cli

RUN set -ex; \
    apt-get update ; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
		zip unzip \
		zlib1g-dev libzip-dev \
        git ssh; \
    rm -rf /var/lib/apt/lists/*

RUN eval `ssh-agent -s`

# Install GitHub CLI.
ENV GITHUB_CLI_VERSION 2.25.1
RUN set -ex; \
    curl -L "https://github.com/cli/cli/releases/download/v${GITHUB_CLI_VERSION}/gh_${GITHUB_CLI_VERSION}_checksums.txt" -o checksums.txt; \
    curl -OL "https://github.com/cli/cli/releases/download/v${GITHUB_CLI_VERSION}/gh_${GITHUB_CLI_VERSION}_linux_arm64.deb"; \
    shasum --ignore-missing -a 512 -c checksums.txt; \
	dpkg -i "gh_${GITHUB_CLI_VERSION}_linux_arm64.deb"; \
	rm -rf "gh_${GITHUB_CLI_VERSION}_linux_arm64.deb"; \
    # verify gh binary works
    gh --version;
RUN docker-php-ext-install zip

# Install composer.
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /tmp
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install WP CLI
RUN curl https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar > /root/wp-cli.phar && \
	chmod +x /root/wp-cli.phar && \
	mv /root/wp-cli.phar /usr/local/bin/wp

# Install Node & NPM.
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash -
RUN apt-get install -y \
  --no-install-recommends nodejs \
  && rm -rf /var/lib/apt/lists/*

# Copy release steps.
COPY release-steps.sh /usr/bin/release-steps.sh

# Copy ssh confid and keys! Not secure!
# TODO: https://www.fastruby.io/blog/docker/docker-ssh-keys.html
COPY ssh-data /root/.ssh

CMD ["bash"]
