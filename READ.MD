1 - Clonando aplicação<br>
<i>git clone https://github.com/marcioloovee/backend-developer</i><br>
<br>
2 - Entre no diretório<br>
<i>cd backend-developer</i><br>
<br>
3 - Instalando as dependências<br>
<i>composer install</i><br>
<br>
4 - Executando a aplicação<br>
<i>php -S localhost:8000 -t public</i><br>
<br>
4 - Rodando a aplicação no navegador<br>
<i>http://localhost:8000</i><br>
<br>
<h3>Enviando arquivo de vendas para processamento</h3>
<br>
<b>POST</b> http://localhost:8000/process_file<br>
PARÂMETROS<br>
<i>file</i> Arquivo TXT<br>