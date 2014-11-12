<?php

namespace Aria2Client;

interface Aria2Interface
{
    // addUri
    public function addURL($url, $file=null);
    // remove
    // forceRemove
    // pause
    // pauseAll
    // forcePause
    // forcePauseAll
    // unpauseAll
    // tellStatus
    // getUris
    // getFiles
    // getPeers
    // tellActive
    // tellWaiting
    // tellStopped
    // changePosition
    // changeUri
    // getOption
    // changeOption
    // getGlobalOption
    // changeGlobalOption
    // getGlogbalStat
    // purgeDownloadResult
    // removeDownloadResult
    // shutdown
    // forceShutdown
}
