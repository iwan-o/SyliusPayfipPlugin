<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Bridge\Models;

use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenInterface;

interface XmlModelInterface
{

    public function __construct(string $data);

    public function getXml(): string;

    public function getDomDocument(): \DOMDocument;

}
