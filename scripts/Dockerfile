FROM composer:2 AS composer
FROM php:7.4-cli

RUN set -ex; \
    echo "deb http://deb.debian.org/debian bullseye-backports main" > /etc/apt/sources.list.d/bullseye-backports.list; \
    apt-get update ; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
		zip unzip \
		zlib1g-dev libzip-dev \
        git/bullseye-backports ssh \
		jq; \
    rm -rf /var/lib/apt/lists/*

RUN eval `ssh-agent -s`

# Install GitHub CLI.
ENV GITHUB_CLI_VERSION 2.32.1
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

# Copy a script with release PR steps.
COPY release-pr-steps.sh /usr/bin/release-pr-steps.sh
COPY update-version.sh /usr/bin/update-version.sh
COPY changelog-unwrap-pr-links.sh /usr/bin/changelog-unwrap-pr-links.sh

# Copy ssh confid and keys! Not secure!
# TODO: https://www.fastruby.io/blog/docker/docker-ssh-keys.html
COPY ssh-data /root/.ssh

CMD ["bash"]
