<?php

include 'classes/getID3/getid3.php';

class Audio
{

    private $data = [];

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function process($term)
    {

        $this->print_step('GERANDO AUDIO');
        $this->fetchAudiosOfAllSentences();
        $this->print_step('Definindo o tempo de cada áudio');
        $this->getPlaytimeAudios();

        return $this->data;
    }

    private function getPlaytimeAudios()
    {
        foreach ($this->data as $index => $item) {


            $this->data[$index]['audio_playtime'] = $this->getPlaytime($item['audio']);
            $this->print_step('Tempo do Audio' . $item['audio'] . ': ' . $this->data[$index]['audio_playtime']);
        }

    }

    private function getPlaytime($audio){

        $getID3 = new getID3;
        $ThisFileInfo = $getID3->analyze($audio);

        return $ThisFileInfo['playtime_seconds'];

    }


    private function fetchAudiosOfAllSentences()
    {
        foreach ($this->data as $index => $item) {

            $this->print_step('Gerando audio para: ' . $item['sentence']);
            $this->data[$index]['audio'] = $this->fetchAudioOfSentence($item['sentence'], $index);
        }
    }

    private function fetchAudioOfSentence($sentence, $index)
    {

        $output = 'content/' . $index . '_audio.wav';
        $service_url = 'https://api.us-south.text-to-speech.watson.cloud.ibm.com/v1/synthesize';
        //não consegui fazer o output do curl php funcionar e por isso coloquei o exec
        exec('curl -X POST -u "apikey:' . Credentials::AUDIO_TXT_TO_SPEECH_API_KEY . '" --header "Content-Type: application/json" --header "Accept: audio/mp3" --data "{\"text\":\"' . str_replace('"', '', $sentence) . '\"}" --output ' . $output . ' "' . $service_url . '"');

        $this->print_step('Áudio gerado em ' . $output);

        return $output;
    }

    private function print_step($text)
    {
        echo '[AUDIO-PROCESS]->' . $text . PHP_EOL;
    }

}