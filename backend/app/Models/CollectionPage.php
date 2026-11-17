<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * COLLECTION_PAGE (règle 8) : une ligne de la table pivot entre collections et adresses.
 *
 * Clé primaire composée (collection_id, position), comme au MLD. Eloquent ne sait pas retrouver un
 * modèle par une clé composée (find(), update() d'un modèle seul) : on ne s'en sert donc pas comme
 * d'un modèle autonome, on passe par les relations Collection::pages() et Adresse::collections()
 * (attach, detach, sync), qui écrivent la table pivot par ses deux colonnes.
 */
#[Table('collection_pages', timestamps: false)]
class CollectionPage extends Pivot
{
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }
}
