<?php

class Video
{

    private $data = [];

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function process($term)
    {
        $this->print_step('Iniciando geração de vídeo');
        $this->print_step('tratando imagens');
        $this->convertAllImages();
        $this->createAllVideosWithSubtitles();
        $this->createYouTubeThumbnail();
        $this->concatAllVideos();

        return $this->data;

    }


    private function concatAllVideos()
    {

        $this->print_step('Iniciando a concatenação dos videos');

        $list_path = 'content/list.txt';
        $file = fopen($list_path, 'w');
        $text = '';
        $output = 'content/final_video.mp4';

        foreach ($this->data as $index => $item) {
            $text .= 'file ' . str_replace("content/", "", $item['video']) . PHP_EOL;
        }
        fwrite($file, $text);
        fclose($file);

        $loglevel = '-loglevel panic';
        $makeMovieFfmpeg = "ffmpeg " . $loglevel . " -f concat -safe 0 -i content/list.txt i- content/background_song.mp3  -c copy -y ".$output;
        print_r(exec($makeMovieFfmpeg, $ret, $err));
        $this->print_step('Video Finalizado: disponível em :' . $output);
    }

    private function createAllVideosWithSubtitles()
    {
        foreach ($this->data as $index => $item) {

            $this->data[$index]['subtitle'] = $this->createSubtitle($item['sentence'], $item['audio_playtime'], $index);
            $this->data[$index]['video'] = $this->renderVideoWithFFMPEG($index, $this->data[$index]['subtitle'], $item['audio_playtime']);

        }
    }

    private function renderVideoWithFFMPEG($index, $subtitle, $playtime)
    {
        $this->print_step('Criando video: ' . $index);//

        $playtime = round($playtime);
        $playtime += 2;
        $output_without_subtitle = 'content/' . $index . '_video_without_subtitle.mp4';
        $output = 'content/' . $index . '_video.mp4';
        $audio_with_silence = 'content/' . $index . '_audio_with_silence.wav';

        //-loglevel panic
        $loglevel = '-loglevel panic';
        //$loglevel = '';
        $makeMovieFfmpeg = "ffmpeg " . $loglevel . "  -framerate 1/" . $playtime . " -vcodec mjpeg -i content/" . $index . "_converted.png -c:v libx264 -r 30 -pix_fmt yuv420p -y " . $output_without_subtitle;
        print_r(exec($makeMovieFfmpeg, $ret, $err));
        //add 1 second of silence before
        $makeMovieFfmpeg = "ffmpeg " . $loglevel . " -i content/silence.wav -i content/silence.wav -i content/" . $index . "_audio.wav -filter_complex [0:0][1:0]concat=n=3:v=0:a=1[out] -map [out] -y " . $audio_with_silence;
        print_r(exec($makeMovieFfmpeg, $ret, $err));
        $makeMovieFfmpeg = "ffmpeg " . $loglevel . " -i " . $output_without_subtitle . " -i " . $audio_with_silence . " -vf \"subtitles=" . $subtitle . ":force_style='FontName=Arial,FontSize=14,PrimaryColour=&Hffff00&'\" -c:v libx264 -r 30 -pix_fmt yuv420p -y " . $output;
        print_r(exec($makeMovieFfmpeg, $ret, $err));

        unlink($output_without_subtitle);
        unlink($audio_with_silence);

        $this->print_step('Video Criado');//
        return $output;

    }

    private function createYouTubeThumbnail()
    {
        $this->print_step('Criando thumbnail');//
        $output_file = "content/thumbnail.png";
        $image = new Imagick('content/0_converted.png');
        $image->adaptiveResizeImage(1280, 720);
        $image->setImageFormat('png');
        file_put_contents($output_file, $image);
        $this->print_step('thumbnail criada' . $output_file);//
    }

    private function createSubtitle($sentence, $playtime, $index)
    {

        $playtime = round($playtime);
        $output = 'content/' . $index . '_subtitle.srt';

        $subtitle = '';
        $ini_sec = 1;
        $end_sec = $playtime + 2;
        $this->print_step('Criando Subtitle para o vídeo ' . $index);

        $subtitle .= '1' . PHP_EOL;
        $subtitle .= '00:00:' . str_pad($ini_sec, 2, "0", STR_PAD_LEFT) . ',000 --> 00:00:' . str_pad($end_sec, 2, "0", STR_PAD_LEFT) . ',000' . PHP_EOL;
        $subtitle .= $sentence . PHP_EOL;

        $file = fopen($output, 'w');
        fwrite($file, $subtitle);
        fclose($file);
        $this->print_step($subtitle);
        $this->print_step('Subtitle Criado');
        return $output;

    }


    private function wordWrapAnnotation(&$image, &$draw, $text, $maxWidth)
    {
        $words = explode(" ", $text);
        $lines = array();
        $i = 0;
        $lineHeight = 0;
        while ($i < count($words)) {
            $currentLine = $words[$i];
            if ($i + 1 >= count($words)) {
                $lines[] = $currentLine;
                break;
            }

            $metrics = $image->queryFontMetrics($draw, $currentLine . ' ' . $words[$i + 1]);
            while ($metrics['textWidth'] <= $maxWidth) {

                $currentLine .= ' ' . $words[++$i];
                if ($i + 1 >= count($words))
                    break;
                $metrics = $image->queryFontMetrics($draw, $currentLine . ' ' . $words[$i + 1]);
            }

            $lines[] = $currentLine;
            $i++;

            if ($metrics['textHeight'] > $lineHeight)
                $lineHeight = $metrics['textHeight'];
        }
        return array($lines, $lineHeight);
    }

    private function convertAllImages()
    {
        foreach ($this->data as $index => $item) {
            $this->data[$index]['converted_image'] = $this->convertImage($index);
        }
    }

    private function convertImage($index)
    {

        $image = new Imagick('content/' . $index . '_original.png');
        $output_file = "content/" . $index . "_converted.png";
        $standard_width = 1920;
        $standard_height = 1080;

        $image_blur = clone $image;

        $image->scaleImage($standard_width, $standard_height, true);

        $image_blur->adaptiveResizeImage($standard_width, $standard_height);
        $image_blur->adaptiveBlurImage(0, 20);

        $geometry = $image->getImageGeometry();

        $width = $geometry['width'];
        $height = $geometry['height'];

        $x = 0;
        $y = 0;

        if($width != $standard_height || $height != $standard_height){
            if ($height == 1080) {
                $x = ($standard_width - $width) / 2;
            } else {
                $y = ($standard_height - $height) / 2;
            }
        }

        $image_blur->compositeImage($image, Imagick::COMPOSITE_OVER, $x, $y);

        file_put_contents($output_file, $image_blur);
        $this->print_step('Imagem:"' . $output_file . '" criada');

        return $output_file;

    }

    private function print_step($text)
    {
        echo '[VIDEO-PROCESS]->' . $text . PHP_EOL;
    }


}