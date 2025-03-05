const get = id => document.querySelector(id)

const pagarmeCreditcardSelected = () => get('#p_method_pagarmev5_creditcard').checked

const clearHash = () => {
  get('#pagarme_card_hash').value = ''
}

const generateHash = (card) => {
  function success(data) {
    console.log(data);
    return true;
  }

  function fail(error) {
    console.error(error);
  }

  PagarmeCheckout.init(success,fail);
  /*
  const encryptionKey = get('#data-pagarmecheckout-app-id').value
  return pagarme.client.connect({
      encryption_key: encryptionKey
    })
    .then(client => client.security.encrypt(card))
    .then((cardHash) => {
      get('#pagarme_card_hash').value = cardHash
    })
   */
}
