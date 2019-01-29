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
function getResultsBySearch(string $query, string $sort = 'date', int $maxResult = 20)
{
    $response = goAPI('https://www.googleapis.com/youtube/v3/search', [
        'part' => 'id', 'q' => trim($query), 'maxResults' => $maxResult, 'key' => APIKey, 'order' => $sort, 'type' => 'video'
    ]);

    /**
     * Преобразуем результат в массив и возвращаем из функции
     */
    return json_decode($response, true);
}

/**
 * Возвращает окончательный массив видеороликов
 * @param array Массив видеороликов
 * @return array
 */
function getResultOrderByViews(array $items = []) 
{
    if (count($items) > 0) {
        $videoIds = implode(',', getVideoIds($items));

        $response = goAPI('https://www.googleapis.com/youtube/v3/videos', [
            'part' => 'snippet,statistics', 'id' => $videoIds, 'key' => APIKey
        ]);

        return json_decode($response, true);
    }

    return [];
}

/**
 * Возвращает ID полученных видеороликов в виде списка
 * @param array $items Массив видеороликов
 * @return array
 */
function getVideoIds(array $items = [])
{
    $result = [];

    if (count($items) > 0) {
        foreach($items as $item) {
            if (isset($item['id']['videoId'])) {
                $result[] = $item['id']['videoId'];
            }
        }
    }

    return $result;
}

function goAPI(string $url, array $params = null)
{
    /**
     * Собираем URL, так как API принимает только GET, то параметры добавляем в ссылку
     */
    if ($params) {
        $paramUrl = http_build_query($params);
    }

    /**
     * Если же быле переданы параметры в аргументе $params, то следует добавить их к финальной ссылке
     */
    $url = isset($paramUrl) ? $url . '?' . $paramUrl : $url;

    $curl = curl_init($url);

    /**
     * Присваиваем параметры Curl
     */
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true, // Чтобы вернул нам результат
        CURLOPT_TIMEOUT => 240 // Чтобы скрипт не завис в случае, когда сайт API выйдет из строя
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}

/**
 * Если вдруг параметр с текстом запроса был отправлен, то осуществляем поиск
 */
if (isset($_GET['query'])) {
    /**
     * Здесь мы получаем только ID видеороликов, которые соответствуют запросу
     */
    $responseSearch = getResultsBySearch($_GET['query']);
    /**
     * Для того, чтобы получить информацию о количестве просмотров, мы должны по результату поиска
     * получить информация о найденных видеороликов. Для этого по ID видеороликов мы через API
     * получим статистику, название и т.д.
     */
    $response = getResultOrderByViews($responseSearch['items'] ?? []);

    /**
     * Сортируем список по количеству просмотров
     */
    usort($response['items'], function ($valueOne, $valueTwo) { 
        return $valueTwo['statistics']['viewCount'] <=> $valueOne['statistics']['viewCount'];
    });
}


/**
 * Выводим на экран
 */
include __DIR__ . '/public/index.html';