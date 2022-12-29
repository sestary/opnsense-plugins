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

class TsigkeyController extends ApiMutableModelControllerBase
{
    protected static $internalModelName = 'tsigkey';
    protected static $internalModelClass = '\OPNsense\Bind\Tsigkey';

    function nameInUse($name, $excludeUUID = null)
    {
        # Loops through all the Acls and ensure the name doesn't exist
        foreach ($this->searchBase('tsigkeys.tsigkey', array("name"))["rows"] as $existingAcl) {
            if ($existingAcl["name"] == $name && $existingAcl["uuid"] != $excludeUUID) {
                return true;
            }
        }

        return false;
    }

    public function searchKeyAction()
    {
        return $this->searchBase('tsigkeys.tsigkey', array("enabled", "name", "algo", "secret"));
    }
    public function getKeyAction($uuid = null)
    {
        $this->sessionClose();
        return $this->getBase('tsigkey', 'tsigkeys.tsigkey', $uuid);
    }
    public function addKeyAction()
    {
        if ($this->request->isPost() && $this->request->hasPost("tsigkey")) {
            if ($this->nameInUse($this->request->getPost("tsigkey")["name"])) {
                return array(
                    "result" => "failed",
                    "validations" => array(
                        "tsigkey.name" => "TSIG Key with this name already exists.",
                    )
                );
            }

            return $this->addBase('tsigkey', 'tsigkeys.tsigkey');
        }

        return array("result" => "failed");
    }
    public function delKeyAction($uuid)
    {
        return $this->delBase('tsigkeys.tsigkey', $uuid);
    }
    public function setKeyAction($uuid)
    {
        if ($this->request->isPost() && $this->request->hasPost("tsigkey")) {
            if ($this->nameInUse($this->request->getPost("tsigkey")["name"],$uuid)) {
                return array(
                    "result" => "failed",
                    "validations" => array(
                        "tsigkey.name" => "TSIG Key with this name already exists.",
                    )
                );
            }

            return $this->setBase('tsigkey', 'tsigkeys.tsigkey', $uuid);
        }

        return array("result" => "failed");
    }
    public function toggleKeyAction($uuid)
    {
        return $this->toggleBase('tsigkeys.tsigkey', $uuid);
    }
}
