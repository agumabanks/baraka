<?php

namespace App\Models;

/**
 * BranchWorker Model - Backward Compatibility Alias
 * 
 * This class now extends the canonical Backend\BranchWorker model.
 * All domain logic, relationships, and business methods are inherited.
 * 
 * @deprecated Use App\Models\Backend\BranchWorker directly
 * 
 * This alias maintains backward compatibility for code that references
 * App\Models\BranchWorker but delegates all functionality to the
 * authoritative Backend\BranchWorker model.
 */
class BranchWorker extends Backend\BranchWorker
{
    // All functionality inherited from Backend\BranchWorker
    // This class serves only as a backward-compatible alias
}
