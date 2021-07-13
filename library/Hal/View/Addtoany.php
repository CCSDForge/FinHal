<?php


namespace Hal\View;

/**
 * Class Addtoany
 * @package Hal\View
 */

class Addtoany implements Tracker
{
    /**
     * @param \Hal_View $view
     */
    protected $view;

    public function __construct($view){

        $this->view = $view;
    }

    public function addHeader()
    {
        // TODO: Implement addHeader() method.
    }

    public function getHeader(): string {
        // TODO: Implement getHeader() method.
        $script = <<< EOV
        <script type="text/javascript">
        (tarteaucitron.job = tarteaucitron.job || []).push('addtoanyshare');
        </script>
EOV;
        return ($script);
    }

    public function followHeader(): bool
    {
        // TODO: Implement followHeader() method.
    }
}
