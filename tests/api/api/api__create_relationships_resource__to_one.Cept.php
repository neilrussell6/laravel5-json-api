<?php

// ----------------------------------------------------
// No POST requests for 'to one' relationships, as they
// would have the same behaviour as a PATCH request.
//
// Specs:
// "A server MUST respond to PATCH requests to a URL
// from a to-one relationship link as described below."
//
// There is no mention of responding DELETE request to
// a to-one relationship.
// ----------------------------------------------------
