# LowAbstractionORM

cosa mi piacerebbe:

vorrei instanziare una volta LowAbsractionORM con le configurazioni fatte così (buttando giù)

    [
        'IS_DEBUG' => false,
        'DB_CONN' => [
            'ENGINE' => 'mysql',
            'HOST' => 'localhost',
            ...
        ]
        // oppure
        'DB_ADAPTER => $qualcosa //che implementa IDBAdapter,
        'CACHE_CONN' => // come db ma implementa ICacheAdapter
        
        'ENTITIES' => [
            User::class,
            Role::class
        ],
        // oppure
        'ENTITIES' => __DIR__.'/folder/blabla/',
        // oppure
        'ENTITES' => function($name) {}
        'MAPS' => // come entities
        'TEMP_FOLDER' => __DIR__.'/var'
    ]
    
a questo punto lui fa le sue cose, se is_debug è falso una volta lette tutte le entità e configurazioni le serializza e 
le butta in un file serializzato (per velocizzare successivi accessi) -> vale la pena? se metto le configurazioni in delle CONST
è più facile da configurare e non ho bisogno di fare tante letture, non so se è più veloce?

Poi dobbiamo adattare tutto il resto, vorrei che sia possibile passare alla configurazione degli adapter
che noi siamo in grado di chiamare nei vari metodi dell'orm (in EntityManager), anche se la vedo parecchio difficille,
comincerei con fare il MysqlAdapter e vedere come và (le join non saprei come renderele dei metodi senza fare il 
QueryBuilder che è un casino)

questo modulo dovrà essere pubblicato in composer per poter essere importato in altri progetti;


alternativa più papabile a questa soluzione è nella cartella "alternativeSrc" che presuppone che le configurazioni siano
scritte nelle costanti delle Entità. vantaggi/svantaggi?

questo modulo dovrà essere pubblicato in composer per poter essere importato in altri progetti

installare composer:

    docker pull composer
    docker run --rm --interactive --tty --volume ${pwd}:/app composer install

lanciare l'app:
    
    docker-compose up