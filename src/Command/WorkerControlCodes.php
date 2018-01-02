<?php

namespace Riverline\WorkerBundle\Command;

final class WorkerControlCodes
{

    const CAN_CONTINUE           = true;

    const EXIT_ON_EXCEPTION      = 101;

    const MEMORY_LIMIT_REACHED   = 102;

    const NO_WORKLOAD            = 103;

    const STOP_EXECUTION         = 104;

    const WORKLOAD_LIMIT_REACHED = 105;

}
