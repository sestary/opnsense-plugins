<?php

/**
 *    Copyright (C) 2022 Robbert Rijkse
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

namespace OPNsense\Bind\Migrations;

use OPNsense\Base\BaseModelMigration;
use OPNsense\Core\Config;
use OPNsense\Bind\Tsigkey;

class Domain1_1_0 extends BaseModelMigration
{
    /**
    * Migrate older keys into new model
    * @param $model
    */
    public function run($model)
    {
        $config = Config::getInstance()->object();

        # Checks to see if there is a bind config section, otherwise skips the rest of the migration
        if (empty($config->OPNsense->bind)) {
            return;
        }

        # This retrieves the existing keys just in case they exist
        $tsigkeyNames = [];
        $tsigHandle = new Tsigkey();
        foreach (($tsigHandle->getNodes())["tsigkeys"]["tsigkey"] as $tsigkeyuuid => $tsigkey) {
            $tsigkeyNames[$tsigkey["name"]] = $tsigkeyuuid;
        }

        $bindConfig = $config->OPNsense->bind;
        # Loops through the domains in the config
        foreach ($bindConfig->domain->domains->domain as $domain) {
            # Checks if the transferkeyname field is empty or has a value
            if (!empty($domain->transferkeyname)) {
                # Checks if the key already exists in the model and adds it to the existing keys
                if (!array_key_exists((string)$domain->transferkeyname, $tsigkeyNames)) {

                    $newkey = $tsigHandle->tsigkeys->tsigkey->add();
                    $newkey->setNodes([
                        'enabled' => 1,
                        'algo' => $domain->transferkeyalgo,
                        'name' => $domain->transferkeyname,
                        'secret' => $domain->transferkey,
                    ]);
                    $tsigkeyNames[(string)$domain->transferkeyname] = $newkey->getAttributes()["uuid"];
                }
    
                $domainModel = $model->getNodeByReference('domains.domain.' . $domain->attributes()["uuid"]);

                # Adds key to the right field for the domain type.
                if ((string)$domainModel->type == "master") {
                    $tsigKeyNode = $domainModel->allowtransfertsigkey->setValue($tsigkeyNames[(string)$domain->transferkeyname]);
                } else {
                    $tsigkeyNode = $domainModel->mastertransfertsigkey->setValue($tsigkeyNames[(string)$domain->transferkeyname]);
                }
            }
        }

        # Save the config for the TSIG Keys
        $tsigHandle->serializeToConfig();
        Config::getInstance()->save();

        parent::run($model);
    }
}
