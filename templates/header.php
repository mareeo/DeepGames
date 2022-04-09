<?php

$quotes = [
    "deep games for deep gamers",
    "fat games for fat gamers",
    "caw games for caw gamers",
    "bideo games for bideo gamers",
    "derp games for derp gamers",
    "fart games for fart gamers",
    "gay games for gay gamers",
    "girl games for girl gamers",
    "juegos profundos para jugadores profundos",
    "ألعاب العميق العميق للاعبين",
    "глубокая игры для глубокого геймеров",
    "erotic games for erotic gamers",
    "based games for based gamers",
    "bark games for bark gamers",

];

$quote = $quotes[array_rand($quotes)];

?>

<div id=header>
    <div id="header-left">
        <a href="/">
            <img src="/images/logo.png" />
        </a>
        <div id=quote><?= $quote; ?></div>
    </div>
    <div id="header-right">
        <ul class="menu">
        <li><a href="/imgdump/" target="_blank">imgDump</a></li>
        </ul>
    </div>

</div>

