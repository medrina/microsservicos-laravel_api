#!/bin/bash

# testa se o .env já existe
if [ ! -f "./msclientes/.env" ]; then
    USER=$(whoami)
    
    # gera o .env do microsserviço msclientes, e aplica permissão de acesso ao usuário corrente do sistema operacional
    cp ./msclientes/.env.example $(pwd)/msclientes/.env
    sudo chown $USER:$USER $(pwd)/msclientes/.env

    # gera o .env do microsserviço mscartoes, e aplica permissão de acesso ao usuário corrente do sistema operacional
    cp ./mscartoes/.env.example $(pwd)/mscartoes/.env
    sudo chown $USER:$USER $(pwd)/mscartoes/.env

    # gera o .env do micorsserviço msavaliador, e aplica permissão de acesso ao usuário corrente do sistema operacional
    cp ./msavaliador/.env.example $(pwd)/msavaliador/.env
    sudo chown $USER:$USER $(pwd)/msavaliador/.env

    # gera chave JWT
    CHAVE_JWT_UNICA=$(openssl rand -base64 32)

    # replica a mesma chave JWT para todos o .env de cada microsserviço
    echo "JWT_SECRET=\"$CHAVE_JWT_UNICA\"" >> $(pwd)/msclientes/.env
    echo "JWT_SECRET=\"$CHAVE_JWT_UNICA\"" >> $(pwd)/mscartoes/.env
    echo "JWT_SECRET=\"$CHAVE_JWT_UNICA\"" >> $(pwd)/msavaliador/.env

fi

# executa o build e a inicialização de todos os containers da API
sudo docker compose up -d
