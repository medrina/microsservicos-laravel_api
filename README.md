# Avaliador de Crédito
Esse Projeto consiste em uma API que realiza avaliações de cartões de crédito conforme a renda do cliente.<br>

A API foi desenvolvida sob uma arquitetura de 3 microsserviços, são eles:
- MSCLIENTES: responsável por cadastrar e gerenciar os clientes;
- MSCARTOES: responsável por cadastrar e gerenciar os cartões de crédito;
- MSAVALIADOR: responsável por cadastrar os usuários administradores, e operar requisitando os serviços disponibilizados através dos endpoints dos microsserviços MSCLIENTES e MSCARTOES.
## Estrutura
Abaixo seguem os dados técnicos:
- Ambiente de execução: Docker
- Microsserviços: Framework Laravel 12 rodando sob o PHP 8.3 (cli)
- Nginx: Servidor Web sendo utilizado como proxy reverso para os 3 microsserviços;
- MariaDB: Banco de dados de cada microsserviço;
- RabbitMQ: Broker de mensageria para armazenamento temporário das solicitações de cartão de crédito;
- Redis: Armazenamento e conferência de tokens inválidos em memória;
- Swagger UI: Documentação de funcionamento dos endpoints dos Microsserviços da API.
- <b>NOTA 1: </b>não é necessário baixar e instalar os programas e aplicativos acima mencionados no seu computador. Eles serão baixados, instalados e configurados dentro do ambiente do Docker.
- <b>NOTA 2: </b>todas as imagens (exceto o Swagger UI) utilizadas, são provenientes dos repositórios oficiais de cada tecnologia/linguagem catalogada no site do Docker Hub. A instalação do Swagger UI ocorre separadamente pelo gerenciador de pacotes Composer de cada Microsserviço, durante a construção das imagens através do Dockerfile.
## Requisitos Técnicos
Dois requisitos importantes a serem explicados:
1) a API está configurada para executar dentro do ambiente em Docker;<br>
2) O seu sistema operacional que você irá clonar o projeto da API, precisa possuir um <b>kernel</b> em Linux baseado no Debian ou Ubuntu (devido ao <i>bind-mount</i>).<br><br>
<b>NOTA 1: </b> No sistema operacional Windows (utilizando o WSL 2), a API não se comporta da mesma maneira quando o sistema operacional hospedeiro é diferente do Linux, ocasionando erros nas inicializações dos containers, devido ao bind-mount.<br><br>
<b>NOTA 2: </b>o termo <i>bind-mount</i> se refere a um sistema de mapeamento do sistema operacional hospedeiro do projeto (sua máquina física), sendo espelhado com o mesmo projeto sendo executado dentro de um container em Docker. Qualquer alteração que é realizada nos arquivos do projeto do sistema hospedeiro, reflete instantaneamente nos containers, e vice-versa.<br><br>
<b>NOTA 3: </b>o bind-mount estará configurado e disponível para novas implementações de funções adicionais na estrutura da API<br>. A pasta de todo o projeto poderá ser aberto pelo seu editor de código, uma vez que qualquer alteração que você realizar nos código-fontes, replicará nos fontes dos microsserviços.<br>
Concluindo, a API requer os seguintes pré-requisitos abaixo:
- acesso à internet
- Sistema Operacional Linux (Ubuntu 22.04, Ubuntu 24.04, Debian 12 ou 13)
- Docker instalado*
- Docker Compose instalado*<br><br>
<b>NOTA<sup>Docker</sup>: </b>Caso você não tenha o Docker instalado no seu Sistema Operacional Linux, o passo-a-passo da <b>Instalação</b>, lhe explicará de como instalar.
## Instalação
1) no seu sistema operacional Linux, abrir o terminal<br>
2) no terminal, acessar o diretório do seu usuário: <b>`cd /home/$USER/`</b><br>
<b>NOTA: </b> Se você tiver o Docker já instalado no seu sistema operacional Linux, desconsidere as etapas 3) e 4), e avançe à etapa 5). Mas se você não tiver o Docker instalado no seu computador, prossiga na etapa 3) e 4).
3) baixar o script de configuração do repositório oficial do Docker digitando: <b>`wget https://get.docker.com -O docker-linux.sh`</b><br>
4) instalar a versão oficial do Docker no Linux. Aguardar as operações e liberação do terminal, digite: <b>`sudo sh docker-linux.sh`</b><br>
5) clonar o projeto via terminal do Ubuntu, digite: <b>`git clone https://github.com/medrina/microsservicos-laravel_api.git`</b><br>
6) acessar o diretório do projeto, digite: <b>`cd microsservicos-laravel_api/`
</b><br>
7) executar o shell-script de geração das imagens e inicialização dos containers. Aguardar as operações e liberaçao do terminal, digite: <b>`sudo sh start.sh`</b><br>
8) acessar a documentação do Swagger de cada microsserviço. Digitar na url do navegador cada rota abaixo:<br>
Microsserviço MSAVALIADOR: http://localhost:8000/msavaliador/documentation<br>
Microsserviço MSCLIENTES: http://localhost:8000/msclientes/documentation<br>
Microsserviço MSCARTOES: http://localhost:8000/mscartoes/documentation<br>
