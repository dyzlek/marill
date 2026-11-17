<?php

namespace App\Exceptions;

use DomainException;

/**
 * Une règle du modèle de données (docs/cahier-des-charges.md, livrable 3) serait violée.
 *
 * Les mêmes règles sont tenues par la base (contraintes CHECK, index unique partiel) : la base est
 * la dernière barrière, même pour une écriture qui contourne les modèles (tinker, requête SQL).
 * Le modèle vérifie avant d'écrire pour donner un message en français qui nomme la règle, au lieu
 * d'une « Integrity constraint violation » de SQLite.
 *
 * C'est une erreur de programmation (un contrôleur doit valider avant d'enregistrer) : elle n'est
 * pas traduite en 422, elle reste un 500 journalisé.
 */
final class RegleNonRespectee extends DomainException {}
