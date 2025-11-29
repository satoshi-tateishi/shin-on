# ==============================================================
# shin-on Production Dockerfile
# ==============================================================
# Base: serversideup/php:8.4-fpm-apache
# Added: PHP GD extension for PDF generation (Dompdf)
# ==============================================================

FROM serversideup/php:8.4-fpm-apache

LABEL maintainer="shin-on"

# Switch to root to install PHP extensions
USER root

# Install PHP GD extension for Dompdf PDF generation
# GD is required for image processing in PDFs
RUN install-php-extensions gd

# Switch back to non-root user
USER www-data
