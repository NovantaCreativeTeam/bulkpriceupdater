# Bulk Price Updater

Modulo Prestashop per l'aggiornamento massivo dei prezzi dei prodotti. Aggiunge le funzionalità per:

- Creazione di un file di esportazione prodotti selezionabili per Categoria e Fornitore
- Importazione del file csv creato per l'aggiornamento massivo dei prezzi

-------------

## Implementazione tecnica

L'intero modulo è stato sviluppato utilizzando quanto più possibile i concetti della "Modern Architecture" di Prestashop. Questo modulo vuole essere un esempio pratico dei nuovi concetti introdotti a partire dalla versione 1.7, come i Symfony Controller, Service Container, CQRS, Grid Components, ecc.. 

### Esportazione

Il processo di esportazione permette all'utente di generare un file csv contenente la lista dei prodotti recuperati in base a determinate categorie e fornitori scelti dall'utente.

Il processo di esportazione viene gestito tramite il controller Symfony `ExportController` che si occupa di mostrare la form di esportazione e di generare il CSV. L' `ExportController` ha solamente l'azione `indexAction` che quando viene invocata in `GET` mostra la form, mentre quando invocata in `POST` genera il file CSV con la lista prodotti.
In questo processo è stato utilizzato in pattern CQRS, in particolare viene utilizzata la query `GetProductsForBulkPriceUpdate` che, gestita dal `GetProductsForBulkPriceUpdateHandler`, restutisce la lista prodotti che verrà utilizzata per la creazione del CSV.
L'handler verrà invocato tramite il Query Bus secondo i principi del CQRS:

    $productQuery = new GetProductsForBulkPriceUpdate($filters);
    $products = $this->get('prestashop.core.query_bus')->handle($productQuery);


### Importazione

Il processo di importazione permette all'utente di reimportare il file csv (generato precedentemente) con i prezzi modificati; il modulo si occuperà di aggiornare i prezzi massivamente.

Il processo di importazione utilizza parte dei componenti di Prestashop che vengono utilizzati per l'importazione dei file CSV dei Prodotti, Categorie, ecc..
Lato FrontEnd sono stati utilizzati i componenti `Uploader` e `Importer` che si occupano rispettivamente di caricare effettuare l'upload del file CSV e l'importazione massiva dei prezzi. La logica di importazione è gestita tramite il controller Symfony `ImportController` che utilizza alcuni servizi di importazione del core registrati all'interno del Container (`prestashop.core.import.importer`, `prestashop.core.import.file_uploader`) ed altri servizi specifici definiti dal modulo stesso contenenti la logica di aggiornamento dei prezzi.

Il codice Javascript viene gestito, compilato e ottimizzato tramite npm e webpack. I sorgenti js si trovano all'interno dell cartella _dev
