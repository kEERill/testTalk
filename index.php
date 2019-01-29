<?php

date_default_timezone_set('UTC');

/**
 * Ключ API, полученный в https://console.developers.google.com
 */
define('APIKey', 'AIzaSyAiTl8iIG6CCWBS9AfGV2B2jvs0vSgedRw');

/**
 * Возвращает массив с результатами поиска
 * 
 * @param string $query Текст запроса
 * @param string $sort Способ сортировки (по умолчанию: по количеству просмотров)
 * @param int $maxResult Количество видео
 * @return array
 */
function getResultsBySearch(string $query, string $sort = 'viewCount', int $maxResult = 20)
{
    /**
     * Собираем дату на неделю назад, для того чтобы получить видео не старше одной недели
     */
    $dateTime = new DateTime;
    $dateTime->sub(new DateInterval('P1W'));
    $dateTime->setTime(0, 0);

    /**
     * Собираем URL, так как API принимает только GET, то параметры добавляем в ссылку
     */
    $baseUrl = 'https://www.googleapis.com/youtube/v3/search';
    $afterUrl = http_build_query([
        'part' => 'snippet', 
        'q' => trim($query), 
        'maxResults' => $maxResult, 
        'key' => APIKey, 
        'order' => $sort, 
        'type' => 'video', 
        'publishedAfter' => $dateTime->format(DATE_ATOM)
    ]);

    $curl = curl_init($baseUrl . '?' . $afterUrl);

    /**
     * Присваиваем параметры Curl
     */
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true, // Чтобы вернул нам результат
        CURLOPT_TIMEOUT => 240 // Чтобы скрипт не завис в случае, когда сайт API выйдет из строя
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    /**
     * Преобразуем результат в массив и возвращаем из функции
     */
    return json_decode($response, true);
}

/**
 * Если вдруг параметр с запросом был отправлен, то осуществляем поиск
 */
if (isset($_GET['query'])) {
    $response = getResultsBySearch($_GET['query']);
}

/**
 * Выводим на экран
 */
include __DIR__ . '/public/index.html';