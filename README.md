# video-maker-php
Este código gera um vídeo com legenda e áudio de um assunto qualquer

#Como funciona?

Digite na linha de comando na raiz do projeto:

```bash
php index.php "Qualquer termo"
```

1-) O script irá buscar no wikipedia (Inglês) o texto relacionado ao termo utilizando o Algorithmia API<br>
2-) As frases serão separadas e as primeiras serão selecionadas<br>
3-) Utilizando a IBM Natural Language Understanding API será definido as palavras-chaves de cada frase.<br>
4-) Utilizando a Google Search API será feito o download das imagens de acordo com as palavras-chaves<br>
5-) Utilizando a IBM Text-to-Speech API será feito o download o áudio de cada frase.<br>
6-) Utilizando o ImageMagik do PHP as imagens serão tratadas para ficarem na resolução 1920x1080<br>
7-) Utilizando o FFMPEG será criado um vídeo com legendas e áudio.<br>

#Pré-requisitos?

Para fazer tudo isso funcionar você precisar de algumas coisas:<br>
<br>
<ul>
<li>Chave de Acesso do Algorithmia API</li>
<li>Chave de Acesso do Natural Language Understanding API</li>
<li>Chave de Acesso do Google Search API e um Search Engine ID</li>
<li>Adicionar a biblioteca ImageMagik no PHP</li>
<li>Ter instalado no seu computador o FFMpeg (ser for windows precisa adicionar nas variavéis de ambiente)</li>
</ul>




