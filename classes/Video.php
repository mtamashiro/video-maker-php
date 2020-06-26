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
        $this->createAllSentenceImages();
        $this->createYouTubeThumbnail();

        return $this->data;

    }

    private function createYouTubeThumbnail(){
        $this->print_step('Criando thumbnail');//
        $output_file = "content/thumbnail.png";
        $image = new Imagick('content/0_converted.png');
        $image->adaptiveResizeImage(1280, 720);
        $image->setImageFormat('png');
        file_put_contents($output_file, $image);
        $this->print_step('thumbnail criada'. $output_file);//
    }

    private function createAllSentenceImages()
    {
        foreach ($this->data as $index => $item) {
            $this->data[$index]['sentence_image'] = $this->createSentenceImage($index);
        }
    }

    private function createSentenceImage($index)
    {
        $output_file = "content/" . $index . "_sentence.png";
        $standard_width = 1920;
        $standard_height = 1080;

        $image = new Imagick();
        $draw = new ImagickDraw();
        $pixel = new ImagickPixel('transparent');
        /* New image */
        $image->newImage(1920, 1080, $pixel);
        /* Black text */
        $draw->setFillColor('white');
        /* Font properties */
        $draw->setFont('arial');
        $draw->setFontSize(30);
        $draw->setTextAlignment(\Imagick::ALIGN_CENTER);
        $draw->setGravity(\Imagick::GRAVITY_CENTER);

        list($lines, $lineHeight) = $this->wordWrapAnnotation($image, $draw, $this->data[$index]['sentence'], 1000);
        for ($i = 0; $i < count($lines); $i++){
            $image->annotateImage($draw, $standard_width/2, ($standard_height/2) + $i * $lineHeight, 0, $lines[$i]);
            echo $i;
        }


        /* Give image a format */
        $image->setImageFormat('png');
        file_put_contents($output_file, $image);
        $this->print_step('Imagem:"' . $output_file . '" criada');//
        return $output_file;
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
        if ($height == 1080) {
            $x = ($standard_width - $width) / 2;
        } else {
            $y = ($standard_height - $width) / 2;
        }


        $image_blur->compositeImage($image, Imagick::COMPOSITE_OVER, $x, $y);

        file_put_contents($output_file, $image_blur);
        $this->print_step('Imagem:"' . $output_file . '" criada');

        return $output_file;

    }

    private function print_step($text)
    {
        echo '[TEXT-PROCESS]->' . $text . PHP_EOL;
    }


}