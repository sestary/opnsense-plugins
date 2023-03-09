<?php

/*
 * Copyright (C) 2022 Robbert Rijkse
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace OPNsense\Bind\Migrations;

use OPNsense\Base\BaseModelMigration;
use OPNsense\Core\Config;
use OPNsense\Bind\General;
use OPNsense\Bind\Forwarder;
use Exception;

class M1_2_0 extends BaseModelMigration
{
    /**
    * Migrate older keys into new model
    * @param $model
    */
    public function run($model)
    {
        if ($model instanceof General) {
            $config = Config::getInstance()->object();

            /* checks to see if there is a bind config section, otherwise skips the rest of the migration */
            if (empty($config->OPNsense->bind)) {
                return;
            }

            $forwarderHandle = new Forwarder();
            $bindConfig = $config->OPNsense->bind;

            /* Check that there are forwarders configured */
            if (!empty((string)$bindConfig->general->forwarders)) {
                $UUIDlist = array();
                /* Loops through all the forwarders in the general config */
                foreach (explode(",", $bindConfig->general->forwarders) as $forwarder) {
                    $newforwarder = $forwarderHandle->forwarders->forwarder->add();
                    $newforwarder->setNodes([
                        'enabled' => 1,
                        'ip' => (string)$forwarder,
                        'port' => 53, 
                    ]);
                
                    $UUIDlist[] = $newforwarder->getAttributes()['uuid'];
                }
 
                /* Save the config for the TSIG Keys */
                $forwarderHandle->serializeToConfig();
                Config::getInstance()->save();

                /* Add forwarder UUIDs to new list of forwarders */
                $model->getNodeByReference('forwarders')->setValue((string)implode(',', $UUIDlist));
            }

            parent::run($model);
        }
    }
}
