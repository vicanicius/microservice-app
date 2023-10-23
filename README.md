## About App

App dede estudo sobre microserviços, integrando a https://newsapi.org/  

## Primeiros passos

* docker-compose up -d
* entrar no container e rodar php artisan migrate 
* importar as models para o scout:  php artisan scout:import "App\Models\TopHeadlines" e  php artisan scout:import "App\Models\News"
* criar uma rede personalizada: docker network create --driver bridge minha-rede-local
* collection do postman anexada ao projeto

