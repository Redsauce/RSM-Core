<?php

class RSMitemWriteException extends RuntimeException
{
    // A required item write or its transaction could not be completed.
}

class RSMmetadataReadException extends RuntimeException
{
    // Item/category definitions required for a write could not be read.
}

class RSMbinaryUploadException extends RuntimeException
{
    // A binary upload, global replacement or import transaction failed.
}

class RSMbinaryConfigurationException extends RuntimeException
{
    // The configured binary size limit cannot be used safely.
}

class RSMresponseWriteException extends RuntimeException
{
    // A response could not be written to its temporary stream.
}

class RSMimageConversionException extends RuntimeException
{
    // The image decoder, encoder or required extension is unavailable.
}

class RSMtokenWriteException extends Exception
{
    // A token relationship or token write prevented the operation.
}
