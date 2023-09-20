<?php
namespace Leemarket\Yc\Translation;

use \Leemarket\Yc\Auth;

Class Translation
{
    private $iamToken;

    function __construct(String $iamToken)
    {
        $this->iamToken = $iamToken;
    }

    private function query($uri, $fields = false)
    {
        if (empty($this->iamToken)) throw new \Exception('No token');
        $auth_head = "Authorization: Bearer " . $this->iamToken;
        if ($fields) $fields = json_encode($fields);
        $result = Auth::query($uri, $fields, false, $auth_head);
        return !empty($result) ? json_decode($result, true) : $result;
    }

    public function get($text, $targetLang = 'en', $sourceLang = 'ru', $isHtml = false){
        $arTexts = is_array($text) ? $text : array($text);
        $format = $isHtml ? 'HTML' : 'PLAIN_TEXT';

        $arTranslation = $this->query("https://translate.api.cloud.yandex.net/translate/v2/translate",['sourceLanguageCode' => $sourceLang, 'targetLanguageCode' => $targetLang, 'format' => $format, 'texts' => $arTexts]);
        if(isset($arTranslation['translations']) && count($arTranslation['translations']) == 1) return $arTranslation['translations'][0];
        elseif(isset($arTranslation['translations']) && count($arTranslation['translations']) > 1) return $arTranslation['translations'];
        else return $arTranslation;
    }


}