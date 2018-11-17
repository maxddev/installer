<?php

namespace Helpflow\Installer\Service;

class SellMyGit
{
    /** @var string */
    protected $filename;
    /** @var string */
    protected $unzippedName;
    /** @var string */
    protected $errorMsg;

    /**
     * @param string $path
     * @param string $licenseKey
     * @return bool
     */
    public function getFile($path, $licenseKey)
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => '',
        ]);

        try {
            $response = $client->request(
                'GET',
                'https://sellmygit.com/api/download/latest/6958017c-4bd2-4769-9d0b-e253ad98eaa2',
                [
                    'headers' => [
                        'smg-download-type' => 'application/zip',
                        'smg-auth' => $licenseKey
                    ]
                ]
            );
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->errorMsg = $e->getResponse()->getReasonPhrase();

            return false;
        }

        $this->filename = str_replace('attachment; filename=', '', $response->getHeader('Content-Disposition')['0']);

        file_put_contents(
            $path . DS . $this->filename,
            $response->getBody()
        );

        $bits = explode('-', str_replace(['.zip', '0-g'], '', $this->filename));
        unset($bits[2]);
        $this->unzippedName = implode('-', $bits);

        return true;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getUnzippedName()
    {
        return $this->unzippedName;
    }

    /**
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }
}
