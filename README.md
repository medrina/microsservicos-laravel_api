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
- Swagger UI: Documentação de funcionamento dos endpoints dos Microserviços da API.
- <b>NOTA 1: </b>não é necessário baixar e instalar os programas e aplicativos acima mencionados no seu computador. Eles serão baixados, instalados e configurados dentro do ambiente do Docker.
- <b>NOTA 2: </b>todas as imagens (exceto o Swagger UI) utilizadas são dos repositórios oficiais de cada tecnologia/linguagem catalogada no site do Docker Hub. A instalação do Swagger ocorre separadamente pelo gerenciador de pacotes Composer de cada Microsserviço, durante a construção das imagens através do Dockerfile.
## Requisitos Técnicos
Dois requisitos importantes a serem explicados:
1) a API está configurada para executar dentro do ambiente em Docker;<br>
2) O seu sistema operacional que você irá clonar o projeto da API, precisa possuir um <b>kernel</b> em Linux baseado no Debian ou Ubuntu (devido ao <i>bind-mount</i>).<br><br>
<b>NOTA 1: </b> No sistema operacional Windows (utilizando o WSL 2), a API não se comporta da mesma maneira quando o sistema operacional hospedeiro é diferente do Linux, ocasionando erros nas inicializações dos containers, devido ao bind-mount.<br><br>
<b>NOTA 2: </b>o termo <i>bind-mount</i> se refere a um sistema de mapeamento do sistema operacional hospedeiro do projeto (sua máquina física), sendo espelhado com o mesmo projeto sendo executado dentro de um container em Docker. Qualquer alteração que é realizada nos arquivos do projeto do sistema hospedeiro, reflete instantaneamente no container, e vice-versa.<br><br>
<b>NOTA 3: </b>o bind-mount estará configurado e disponível para novas implementações de funções adicionais na estrutura da API<br>. A pasta de todo o projeto poderá ser aberto pelo seu editor de código, uma vez que qualquer alteração que você realizar nos código-fontes, replicará nos fontes dos containers.<br>
Concluindo, a API requer os seguintes pré-requisitos abaixo:
- acesso à internet
- Sistema Operacional Linux (Ubuntu 22.04, Ubuntu 24.04, Debian 12 ou 13)
- Docker instalado*
- Docker Compose instalado*<br><br>
<b>NOTA<sup>Docker</sup>: </b>Caso você não tenha o Docker instalado no seu Sistema Operacional Linux, o passo-a-passo da <b>Instalação</b>, lhe explicará de como instalar.
## Instalação
1) no seu sistema operacional Linux, abrir o terminal<br>
2) no terminal, acessar o diretório do seu usuário: <b>`cd /home/$USER/`</b><br>
<b>NOTA: </b> Se você tiver o Docker já instalado no seu sistema operacional Linux, desconsidere as etapas 3) e 4), e avançe à etapa 5). Mas se você não tiver o Docker instalado no seu computador, prossiga na etapa 3) e 4).<br>
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
## Lógica dos Endpoints
Os parâmetros e os atributos dos objetos JSON, estão discriminados na documentação do Swagger de todos os endpoints dos Microsserviços.<br>
Para realizar as avaliações de crédito, é necessário antes cadastrar os cartões de crédito/bancos e os clientes na API.<br>
Em primeiro lugar, deve-se cadastrar um usuário Administrador, acessando o Microsserviço MSAVALIADOR, e o endpoint <b>register</b><br>
<img width="1804" height="72" alt="MSAVALIADOR-POST-register" src="https://github.com/user-attachments/assets/d52d5643-b645-43bd-bf2a-87d9fc4aadda" />
___________________________________________________________________________
Em seguida, você precisa realizar login com algum usuário Administrador que esteja cadastrado. Quando o usuário Administrador efetua o login, a API retorna uma resposta de confirmaçao de autenticação fornecendo um token de acesso. Esse token serve para acessar as rotas que estão protegidas nos microsserviços. Acessar o Microsserviço MSAVALIADOR, e o endpoint de <b>efetuar login</b><br>
<img width="1789" height="68" alt="MSAVALIADOR-POST-login" src="https://github.com/user-attachments/assets/da758a92-d1af-4feb-bb5e-faf06c8636a5" />
___________________________________________________________________________
Com o token obtido através da autenticação, você (como usuário Administrador) pode cadastrar os clientes e os cartões de crédito/bancos.<br>
Para cadastrar um cartão de crédito/banco, você deve inserir o token de acesso obtido na sua autenticação do endpoint login do microsserviço MSAVALIADOR (<i>para saber mais como inserir o token no endpoint, consulte a seção <b>Swagger</b></i>).<br>
<b>NOTA 1: </b>A renda do cartão a ser cadastrada, refere-se a faixa de renda informada pelo cliente. Ex.: se houver cartões cadastrados, e nesse intervalo, tiver cartões com a faixa de renda no valor de R$ 4.000,00 e outros cartões com a faixa de renda no valor de R$ 3.500,00 , e no momento da avaliação de crédito, a renda informada do cliente for R$ 3.900,00 , a listagem de cartões que estarão disponíveis para esse cliente, retornará até o cartão de renda R$ 3.500,00.<br>
<b>NOTA 2: </b>É interessante você cadastrar várias opções de cartões de crédito, para que o seu cliente possa escolher qual cartão ele prefere.<br>
Acessar o Microsserviço MSCARTOES, e o endpoint de <b>cadastrar um cartão</b><br>
<img width="1789" height="67" alt="MSCARTOES-POST-cartoes" src="https://github.com/user-attachments/assets/ce768609-d20c-4bbe-b4ae-c628cdd180f4" />
___________________________________________________________________________
Para cadastrar um cliente, você deve inserir o token de acesso obtido na sua autenticação do endpoint login do microsserviço MSAVALIADOR. Acessar o Microsserviço MSCLIENTES, e o endpoint de <i>cadastrar um cliente</i><br>
<img width="1791" height="67" alt="MSCLIENTES-POST-clientes" src="https://github.com/user-attachments/assets/b839cc3d-d97e-46f6-800e-d0c5730e1aa5" />
___________________________________________________________________________
Com o cliente e os cartões cadastrados, torna-se habilitada a operação de realizar uma avaliação de crédito de algum cliente.<br>
<b>NOTA 1: </b>Para executar esse endpoint, o CPF a ser informado do cliente precisa estar cadastrado, e a renda do cliente é livre (ou seja, a renda não precisa estar previamente cadastrada).<br>
<b>NOTA 2: </b>Esse endpoint irá processar a avaliação de crédito, comparando a renda do cliente com as faixas de renda dos cartões de crédito. Definido os cartões de crédito que se enquadram dentro da renda do cliente, será efetuado cálculos com a renda e com a idade do cliente, para compor o valor de limite básico (limite inicial) estipulado dos cartões para aquele cliente<br>
<b>NOTA 3: </b>Esse endpoint retornará uma lista de cartões disponíveis ao clientes. Em seguida, o cliente deverá escolher qual o cartão de sua preferência dentre os cartões que forma retornados da lista. Você deve anotar/armazenar os dados do cartão que ele escolher, para executar o endpoint de <i>Solicitar Cartão</i><br>
<b>id do cliente<br>
id do cartão<br>
limite básico inicial</b><br>
Acessar o Microsserviço MSAVALIADOR, e o endpoint de <b>realizar uma avaliação de crédito</b><br>
<img width="1789" height="62" alt="MSAVALIADOR-GET-avaliacao-credito" src="https://github.com/user-attachments/assets/bd0730e8-d3ae-4d8e-81e9-ab2c0848c1ba" />
___________________________________________________________________________
Após realizada a avaliação de crédito com o cliente no endpoint de avaliação de crédito,</b> dentre a lista de cartões retornados, o cliente deverá escolher 1 cartão. Esse cartão escolhido por ele (o cliente), o usuário Administrador (você) terá que informar os 4 dados que são exigidos por esse endpoint de Solicitar Cartão:<br>
- id do cliente;
- id do cartão escolhido pelo cliente;
- renda informada pelo cliente no endpoint de <i>realizar uma avaliação de crédito</i>
- valor do limite básico inicial desse cartão</b><br><br>
Acessar o Microsserviço MSAVALIADOR, e o endpoint de <b>solicitar um cartão de crédto disponível ...</b><br>
<img width="1790" height="69" alt="MSAVALIADOR-POST-solicitar-cartao" src="https://github.com/user-attachments/assets/cf29f92d-77ec-497c-8d41-5e5738678b8f" />
<b>NOTA: </b>você também pode acessar os endpoints das rotas acima através de ferramentas que simulam envio e respostas das requisições HTTP como Insomnia e Postman.
O Swagger foi configurado nos endpoints, por ser intuitivo e fácil interação através da sua interface visual. Também pela praticidade em ser executado no próprio navegador, e ter incluída a documentação em cada endpoint (Pré-condição, Pós-condição, 
exemplos de envio e resposta das requisições HTTP).<br>
