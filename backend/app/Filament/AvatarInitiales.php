<?php

namespace App\Filament;

use App\Models\User;
use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;

/**
 * L'avatar du back-office, dessiné ici : un rond avec les initiales du pseudo, en SVG dans une URL « data: ».
 *
 * Par défaut, Filament demande l'image à ui-avatars.com, un service extérieur, en lui envoyant le nom de
 * l'administrateur. Un pseudo est une donnée personnelle : on ne l'envoie pas à un tiers pour un dessin.
 */
class AvatarInitiales implements AvatarProvider
{
    public function get(Model $record): string
    {
        $initiales = $record instanceof User ? $record->initials() : '?';

        // htmlspecialchars : le pseudo est choisi par le membre, il ne doit pas pouvoir injecter du SVG.
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" rx="32" fill="#b45309"/>'
            .'<text x="32" y="40" font-family="sans-serif" font-size="24" fill="#fff" text-anchor="middle">'
            .htmlspecialchars(mb_strtoupper($initiales), ENT_XML1)
            .'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
