# Pagar.me para Magento 1.9

## Recursos

- [x] Pagamento com PIX
- [x] Pagamento com Cartão de Crédito
- [x] Pagamento com Boleto Bancário

## Dependências

O módulo [Bootstrap para Magento CE](https://github.com/eloom/bootstrap-magento-ce).

## Compatibilidade

- [x] Magento 1.9.4.5
- [x] PHP/PHP-FPM 7.3
- [x] Pagar.me API Versão 4 (2019-09-01)

## Habilitar composer no Magento 1
```
composer init
```
```
"repositories": [
    {
        "type": "composer",
        "url": "https://packages.firegento.com"
    }
]
```

## Instalar módulos
```
composer require --update-no-dev magento-hackathon/magento-composer-installer
```
* defina "." como root dir

## Instalar SDK do Pagar.me
```
composer require guzzlehttp/guzzle:~6.3
composer require pagarme/pagarme-php:v4.1.2
```

## Gerando o build

Os projetos da élOOm utilizam o [Apache Ant](https://ant.apache.org/) para publicar o projeto nos ambientes de **desenvolvimento** e de **teste** e para gerar os pacotes para o **ambiente de produção**.

- Publicando no **ambiente local**

 - no arquivo **build-desenv.properties**, informe o path do **Document Root** na propriedade "projetos.path";

 - na raiz deste projeto, execute, no prompt, o comando ```ant -f build-desenv.xml```.


	> a tarefa Ant irá copiar todos os arquivos do projeto no seu Magento e limpar a cache.


- Publicando para o **ambiente de produção**

 - na raiz deste projeto, execute, no prompt, o comando ```ant -f build-producao.xml```.


	> a tarefa Ant irá gerar um pacote no formato .zip, no caminho definido na propriedade "projetos.path", do arquivo **build-producao.properties**.

	> os arquivos .css e .js serão comprimidos automáticamente usando o [YUI Compressor](https://yui.github.io/yuicompressor/).