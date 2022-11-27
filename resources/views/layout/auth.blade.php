@extends("layout.html")

@section("body")

    @push("body_class")
   h-full w-full
    @endpush

    @push("pure_css")
    body {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' version='1.1' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:svgjs='http://svgjs.com/svgjs' width='1440' height='560' preserveAspectRatio='none' viewBox='0 0 1440 560'%3e%3cg mask='url(%26quot%3b%23SvgjsMask1017%26quot%3b)' fill='none'%3e%3crect width='1440' height='560' x='0' y='0' fill='rgba(68%2c 84%2c 101%2c 1)'%3e%3c/rect%3e%3cpath d='M1369 464L1368 303' stroke-width='6' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M1405 220L1404 504' stroke-width='8' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M994 403L993 822' stroke-width='6' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M998 127L997 345' stroke-width='8' stroke='url(%23SvgjsLinearGradient1019)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M1004 12L1003 -148' stroke-width='10' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M717 409L716 180' stroke-width='10' stroke='url(%23SvgjsLinearGradient1019)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M472 486L471 678' stroke-width='10' stroke='url(%23SvgjsLinearGradient1019)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M95 290L94 124' stroke-width='10' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M121 365L120 164' stroke-width='10' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M287 69L286 397' stroke-width='10' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M260 14L259 347' stroke-width='6' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M619 45L618 435' stroke-width='10' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M1393 93L1392 -177' stroke-width='6' stroke='url(%23SvgjsLinearGradient1019)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M387 77L386 363' stroke-width='8' stroke='url(%23SvgjsLinearGradient1019)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M780 255L779 498' stroke-width='8' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M1083 73L1082 -162' stroke-width='8' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M1420 142L1419 372' stroke-width='8' stroke='url(%23SvgjsLinearGradient1019)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M8 23L7 350' stroke-width='8' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M398 356L397 130' stroke-width='10' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M1360 128L1359 373' stroke-width='10' stroke='url(%23SvgjsLinearGradient1019)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M1138 416L1137 772' stroke-width='6' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M925 122L924 -55' stroke-width='8' stroke='url(%23SvgjsLinearGradient1019)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M1017 49L1016 -103' stroke-width='10' stroke='url(%23SvgjsLinearGradient1019)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M7 7L6 -229' stroke-width='10' stroke='url(%23SvgjsLinearGradient1019)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M1433 54L1432 440' stroke-width='6' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M1249 29L1248 -192' stroke-width='8' stroke='url(%23SvgjsLinearGradient1019)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M537 339L536 669' stroke-width='8' stroke='url(%23SvgjsLinearGradient1019)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M790 466L789 694' stroke-width='6' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M585 239L584 498' stroke-width='8' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M868 418L867 268' stroke-width='6' stroke='url(%23SvgjsLinearGradient1019)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M1225 88L1224 -228' stroke-width='6' stroke='url(%23SvgjsLinearGradient1019)' stroke-linecap='round' class='Up'%3e%3c/path%3e%3cpath d='M631 58L630 -171' stroke-width='6' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M1424 112L1423 -285' stroke-width='6' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M1008 54L1007 195' stroke-width='6' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M387 407L386 565' stroke-width='6' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3cpath d='M598 60L597 -232' stroke-width='6' stroke='url(%23SvgjsLinearGradient1018)' stroke-linecap='round' class='Down'%3e%3c/path%3e%3c/g%3e%3cdefs%3e%3cmask id='SvgjsMask1017'%3e%3crect width='1440' height='560' fill='white'%3e%3c/rect%3e%3c/mask%3e%3clinearGradient x1='0%25' y1='0%25' x2='0%25' y2='100%25' id='SvgjsLinearGradient1018'%3e%3cstop stop-color='rgba(80%2c 94%2c 110%2c 0)' offset='0'%3e%3c/stop%3e%3cstop stop-color='rgba(80%2c 94%2c 110%2c 1)' offset='1'%3e%3c/stop%3e%3c/linearGradient%3e%3clinearGradient x1='0%25' y1='100%25' x2='0%25' y2='0%25' id='SvgjsLinearGradient1019'%3e%3cstop stop-color='rgba(80%2c 94%2c 110%2c 0)' offset='0'%3e%3c/stop%3e%3cstop stop-color='rgba(80%2c 94%2c 110%2c 1)' offset='1'%3e%3c/stop%3e%3c/linearGradient%3e%3c/defs%3e%3c/svg%3e");
    }
    @endpush

    @push("html_class")
    h-full w-full
    @endpush
    <div class="flex h-full w-full items-center justify-center">
        <div>
            @yield("content")
        </div>
    </div>
@endsection