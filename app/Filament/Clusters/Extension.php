<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Extension extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-americas';

    protected static ?string $navigationLabel = '4. Extensión';

    protected static ?string $clusterBreadcrumb = 'Extensión';

    protected static ?string $title = 'Eje 4: Extensión';

    protected static ?int $navigationSort = 4;
}
