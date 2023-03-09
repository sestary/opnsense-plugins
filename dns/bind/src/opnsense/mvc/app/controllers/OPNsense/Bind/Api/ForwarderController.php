<?php

/**
 *    Copyright (C) 2018 Michael Muenz <m.muenz@gmail.com>
 *
 *    All rights reserved.
 *
 *    Redistribution and use in source and binary forms, with or without
 *    modification, are permitted provided that the following conditions are met:
 *
 *    1. Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *    2. Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 *    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 *    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 *    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 *    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 *    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 *    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 *    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 *    POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace OPNsense\Bind\Api;

use OPNsense\Base\ApiMutableModelControllerBase;

class ForwarderController extends ApiMutableModelControllerBase
{
    protected static $internalModelName = 'forwarder';
    protected static $internalModelClass = '\OPNsense\Bind\Forwarder';

    // This ensures that the IP and Port comintation is unique, this isn't possible with the model contraints
    function ipportContraint($ip, $port, $excludeUUID = null)
    {
        foreach ($this->searchBase('forwarders.forwarder', array("ip", "port"))["rows"] as $existingForwarders) {
            if ($existingForwarders["ip"] == $ip && $existingForwarders["port"] == $port && $existingAcl["uuid"] != $excludeUUID) {
                return true;
            }
        }

        return false;
    }

    public function searchForwarderAction()
    {
        return $this->searchBase('forwarders.forwarder', array("enabled", "ip", "port"));
    }
    public function getForwarderAction($uuid = null)
    {
        $this->sessionClose();
        return $this->getBase('forwarder', 'forwarders.forwarder', $uuid);
    }
    public function addForwarderAction()
    {
        if ($this->request->isPost() && $this->request->hasPost("forwarder")) {
            if ($this->ipportContraint($this->request->getPost("forwarders")["ip"], $this->request->getPost("forwarders")["port"])) {
                return array(
                    "result" => "failed",
                    "validations" => array(
                        "forwarder.ip" => "Forwarder with this IP/Port combination already exists.",
                    )
                );
            }

            return $this->addBase('forwarder', 'forwarders.forwarder');
        }

        return array("result" => "failed");
    }
    public function delForwarderAction($uuid)
    {
        return $this->delBase('forwarders.forwarder', $uuid);
    }
    public function setForwarderAction($uuid)
    {
        if ($this->request->isPost() && $this->request->hasPost("acl")) {
            if ($this->ipportContraint($this->request->getPost("forwarders")["ip"], $this->request->getPost("forwarders")["port"], $uuid)) {
                return array(
                    "result" => "failed",
                    "validations" => array(
                        "forwarder.ip" => "Forwarder with this IP/Port combination already exists.",
                    )
                );
            }

            return $this->addBase('forwarder', 'forwarders.forwarder', $uuid);
        }

        return array("result" => "failed");
    }
    public function toggleForwarderAction($uuid)
    {
        return $this->toggleBase('forwarders.forwarder', $uuid);
    }
}
