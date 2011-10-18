<?php

/**
 * Ehh? This could be an interesting one to implement. Could be set as the
 * primary data store for registered data stores in FileBackend. That would
 * cause it to be read first before hitting the real data store. It would also
 * instantly populate the cache with newly stored files
 */