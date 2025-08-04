#!/bin/bash

# CONFIGURAÇÕES
HOSTINGER_USER="u709248946"
HOSTINGER_HOST="juntter.com.br"  # ou IP do servidor
REMOTE_DIR="public_html/checkout"
LOCAL_DIR="public"

# Arquivo temporário de comandos SFTP
SFTP_CMDS=$(mktemp)

# Monta comandos para SFTP
echo "cd $REMOTE_DIR" >> $SFTP_CMDS
echo "lcd $LOCAL_DIR" >> $SFTP_CMDS
echo "mput -r *" >> $SFTP_CMDS

echo "🔄 Iniciando envio SFTP da pasta '$LOCAL_DIR' para '$REMOTE_DIR' em $HOSTINGER_HOST..."

# Executa SFTP
sftp "$HOSTINGER_USER@$HOSTINGER_HOST" < "$SFTP_CMDS"

# Limpa
rm "$SFTP_CMDS"

echo "✅ Deploy concluído com sucesso!"
