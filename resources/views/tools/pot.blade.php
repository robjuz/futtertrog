@php
$lidHeight = 20;
$lidRadius = 5;
$lidGap = 5;
@endphp

<svg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'
     width='100%' height='100%' viewBox="0 0 512 512">
    <path d="
        M{{ 256 - $lidWidth/2 }},{{ 512 - 1 - $potHeight - $lidGap - $lidHeight }}
        h{{ $lidWidth/2 - $lidHandleRadius/2 }}
        a{{ $lidHandleRadius }},{{ $lidHandleRadius  }} 0,1,1 {{ $lidHandleRadius  }},0
        h{{ $lidWidth/2 - $lidHandleRadius/2 }}
        v{{ $lidHeight - $lidRadius }}
        a{{ $lidRadius }},{{ $lidRadius }} 0,0,1, {{ -1*$lidRadius }},{{ $lidRadius }}
        h{{ -1*($lidWidth - 2*$lidRadius) }}
        a{{ $lidRadius }},{{ $lidRadius }} 0,0,1, {{ -1*$lidRadius }},{{ -1*$lidRadius }}
        v{{ -1*($lidHeight - $lidRadius) }}
        z">
    </path>

    <path d="
        M{{ 256 - $potWidth/2 }},{{ 512 - 1 - $potHeight }}
        h{{ $potWidth }}
        v{{ $potHeight - $potRadius }}
        a{{ $potRadius  }},{{ $potRadius  }} 0,0,1, {{ -1*$potRadius  }},{{ $potRadius  }}
        h{{ -1*($potWidth - 2*$potRadius) }}
        a{{ $potRadius  }},{{ $potRadius  }} 0,0,1, {{ -1*$potRadius  }},{{ -1*$potRadius  }}
        v{{ -1*($potHeight - $potRadius) }}
        z">
    </path>
</svg>
