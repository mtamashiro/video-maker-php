<?php



class Text
{
    const ALGORITHMIA_ALGO = 'web/WikipediaParser/0.1.2';
    private $content = '';
    private $content_sentences = [];
    private $data = [];
    const MAXIMUM_SENTENCES = 7;

    public function process($term)
    {
        $this->print_step('Iniciando...');
        $this->print_step('Buscando termos no WIKIPEDIA');
        $this->content = $this->fetchContentFromWikipedia($term);
        $this->print_step('Busca no wikipedia finalizado!');
        $this->print_step('Limpando o conteúdo');
        $this->sanitazeContent();
        $this->breakContentIntoSentences();
        $this->limitMaximumSentences();
        $this->setSentencesInData();
        $this->print_step('definindo keywords');
        $this->fetchWatsonAndReturnKeywords();
        $this->print_step('keywords definidas');
        return $this->data;

    }

    private function fetchContentFromWikipedia($term)
    {
        $service_url = 'https://api.algorithmia.com/v1/algo/' . self::ALGORITHMIA_ALGO;

        $params = '{"articleName": "' . $term . '","lang": "en"}';

        $curl = curl_init($service_url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', "Authorization:" . Credentials::TEXT_ALGORITHIMIA_API_KEY));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        $response = curl_exec($curl);
        $response = json_decode($response);
        return $response->result->content;
    }

    private function sanitazeContent()
    {
        $this->content = $this->removeBlankLinesAndMarkdown();
        $this->content = $this->removeDatesInParentheses();
    }

    private function removeBlankLinesAndMarkdown()
    {
        $aux_content = nl2br($this->content);
        $lines = explode('<br />', $aux_content);
        $contentSanitized = "";

        foreach ($lines as $line) {
            $line = trim($line);
            if (substr($line, 0, 1) != "=" && $line != '') {
                $contentSanitized .= $line;
            }
        }
        return $contentSanitized;
    }

    private function removeDatesInParentheses()
    {

        $newContent = preg_replace('/\((?:\([^()]*\)|[^()])*\)/', '', $this->content);
        $newContent = preg_replace('/  /', ' ', $newContent);

        return $newContent;
    }

    private function breakContentIntoSentences()
    {
        $this->content_sentences = explode('. ', $this->content);
    }

    private function limitMaximumSentences()
    {
        $this->content_sentences = array_slice($this->content_sentences, 0, self::MAXIMUM_SENTENCES);
    }

    private function setSentencesInData()
    {
        foreach ($this->content_sentences as $sentence) {
            $this->data[]['sentence'] = $sentence;
        }
    }

    private function fetchWatsonAndReturnKeywords()
    {

        $service_url = 'https://gateway.watsonplatform.net/natural-language-understanding/api/v1/analyze?version=2019-07-12';

        foreach ($this->content_sentences as $index => $sentence) {
            $data = '{"text":"' . $sentence . '","features":{"keywords":{}}}';
            $curl = curl_init($service_url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($curl, CURLOPT_USERPWD, "apikey:" . Credentials::TEXT_NLU_WATSON_API_KEY);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            $response = curl_exec($curl);
            $response = json_decode($response);

            if(isset($response->keywords)){
                foreach ($response->keywords as $keyword) {
                    $this->data[$index]['keywords'][] = $keyword->text;
                }
            }else{
                $this->data[$index]['keywords'] = [];
            }
        }
    }


    private function print_step($text)
    {
        echo '[TEXT-PROCESS]->' . $text . PHP_EOL;
    }

}