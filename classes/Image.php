<?php

class Image
{

    private $data = [];

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function process($term)
    {

        $this->fetchImagesOfAllSentences($term);
        $this->downloadAndSaveImages();

        return $this->data;
    }


    private function fetchImagesOfAllSentences($term)
    {
        $query = '';

        $this->print_step('Procurando imagens');
        foreach ($this->data as $index => $sentence) {
            if ($index === 0 || !isset($sentence['keywords'][0])) {
                $query = $term;
            } else {
                $query = $term . ' ' . $sentence['keywords'][0];
            }
            $this->print_step('Buscando imagem pelo termo: ' . $query);
            $this->data[$index]['imageUrl'] = $this->fetchGoogleAndReturnImagesLinks($query);
        }
    }

    private function downloadAndSaveImages()
    {

        foreach ($this->data as $index => $item) {

            $fullpath = 'content/' . $index . '_original.png';

            $ch = curl_init($item['imageUrl']);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            $rawdata = curl_exec($ch);
            curl_close($ch);
            if (file_exists($fullpath)) {
                unlink($fullpath);
            }
            $fp = fopen($fullpath, 'x');
            fwrite($fp, $rawdata);
            fclose($fp);

            $this->print_step('Download da imagem: ' . $item['imageUrl'] . ' -> OK');

        }
    }

    private function fetchGoogleAndReturnImagesLinks($query)
    {


        $client = new Google_Client();
        $client->setApplicationName("video-maker");
        $client->setDeveloperKey(Credentials::IMAGE_GOOGLE_API_KEY);
        $service = new Google_Service_Customsearch($client);
        $optParams = array(
            "cx" => Credentials::IMAGE_GOOGLE_SEARCH_ENGINE_ID,
            'q' => $query,
            'searchType' => 'Image'
        );

        $results = $service->cse->listCse($optParams);
        //The results object implements Iterator so we can loop over them as follows:

        $items = $results->getItems();
        return $items[0]['link'];
    }


    private function print_step($text)
    {
        echo '[IMAGE-PROCESS]->' . $text . PHP_EOL;
    }

}