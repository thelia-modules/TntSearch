<?php

return array(
    'Search logs' => 'Historique des recherches',
    'Searches' => 'Recherches',
    'The indexes were rebuilt.' => 'Les index ont été reconstruits.',
    'The indexes could not be rebuilt. The cause is written in the Thelia log.' => 'Les index n\'ont pas pu être reconstruits. La cause est inscrite dans le journal de Thelia.',
    'Thelia indexes the products, categories, contents, folders and brands each time they are saved: this option does not change that.' => 'Thelia indexe les produits, catégories, contenus, dossiers et marques à chaque enregistrement : cette option n\'y change rien.',
    'It only concerns the customer index of the module. When the box is unchecked, that index is only updated when the indexes are rebuilt (button below, or the <code>bin/console tntsearch:indexes</code> command).' => 'Elle ne concerne que l\'index des clients du module. Case décochée, cet index n\'est mis à jour qu\'à la reconstruction des index (bouton ci-dessous, ou commande <code>bin/console tntsearch:indexes</code>).',
    'Update the customer index each time a customer account is saved' => 'Mettre à jour l\'index des clients à chaque enregistrement d\'un compte client',
    'Rebuilds every index of the module: customers, orders, then the catalogue in each language. On a large catalogue, prefer the <code>bin/console thelia:search:reindex</code> command, which works in batches and resumes after an interruption.' => 'Reconstruit tous les index du module : clients, commandes, puis le catalogue dans chaque langue. Sur un gros catalogue, préférez la commande <code>bin/console thelia:search:reindex</code>, qui travaille par lots et reprend après une interruption.',
    'Rebuild the indexes' => 'Reconstruire les index',
);
