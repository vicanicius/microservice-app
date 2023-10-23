## About App

App dede estudo sobre microserviços, integrando a https://newsapi.org/ e o microserviço de resumo de texto(https://github.com/vicanicius/summary-news). 

## Primeiros passos

* docker-compose up -d
* entrar no container e rodar php artisan migrate 
* importar as models para o scout:  php artisan scout:import "App\Models\TopHeadlines" e  php artisan scout:import "App\Models\News"
* deixar o php artisan schedule:run executando
* criar uma rede personalizada: docker network create --driver bridge minha-rede-local
* copiar o .env.example para o .env

## Como usar
* Faça uma requisição no endpoint /api/v1/news/everything para pesquisar uma noticia (collection anexada ao projeto)
* Entrar no container do https://github.com/vicanicius/summary-news e rodar php artisan schedule:run
* Note que o campo 'summary' da tabela News ficará sendo populada por uma fila do rabbitMq, com o resumo da noticia, resumo esse feito pela api AI da smmry(https://smmry.com/).
* obs.: O raabitMq pode ser visto com a UI acessando http://localhost:15672/#/queues
