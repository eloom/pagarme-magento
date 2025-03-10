# Pagar.me para Magento 1.9

## Recursos

- [x] Pagamento com PIX
- [x] Pagamento com Cartão de Crédito
- [x] Pagamento com Boleto Bancário

## Dependências

Módulo [Bootstrap para Magento CE](https://github.com/eloom/bootstrap-magento-ce) para gravação de logs com o Log4php.

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
composer require --update-no-dev magento-hackathon/magento-composer-installer:^4.0
```
* defina "." como root dir

## Instalar SDK do Pagar.me

Baixar "https://github.com/pagarme/pagarme-core-api-php" e colocar em vendor/PagarmeCoreApiLib

Renomear o composer:name do "pagarme-core-api-php" para "pagarme/pagarme-core-lib-v5"

Em "repositories", adicionar 
```
{
      "type": "path",
      "url": "./vendor/pagarme/PagarmeCoreApiLib",
      "options": {
        "symlink": true
      }
}
```

Adicionar em require
```
"pagarme/pagarme-core-lib-v5": "@dev"
```

```
composer require pagarme/pagarme-core-lib-v5:@dev
```

## Webhooks

Marcar os seguintes:

+ order.canceled
+ order.paid
+ order.payment_failed

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
