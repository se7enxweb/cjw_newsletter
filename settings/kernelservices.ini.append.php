<?php /* #?ini charset="utf-8"?

# The newsletter's subscriber import and export in Exponential's transfer registry (kernel 6.0.15 and later,
# settings/kernelservices.ini; the kernel's doc/eng-us/features/6.0/transfer-registry.md). The kernel lists the tool in
# the "Which import or export tool do I need?" help page, the "Looking for…?" boxes of the other tools, the "Import
# and export" lists of the Setup menu and the left menus and the administration's search. Older kernels ignore this
# file.

[Service_transfer.newsletter]
Class=expKernelTransferTool
Factory=expKernelTransferTool::fromSettings
Arguments[]=transfer.newsletter
Scope=worker
Tags[]=transfer.tool
Description=Add subscribers to a newsletter list from a CSV file with a column mapping, see past imports, and download a list's subscribers.
Name=Newsletter subscribers
Direction=both
Scopes[]=data
Formats[]=CSV
EntryURL=newsletter/import_list
Permission=newsletter/import_list|newsletter/subscription_list_csvimport|newsletter/import_export
HelpPage=ext/cjw_newsletter/README.md
RoundTrip[]=transfer.newsletter
Keywords[]=subscribers
Keywords[]=mailing list
Keywords[]=email addresses
Keywords[]=Abonnenten
Keywords[]=Verteiler
Keywords[]=E-Mail-Adressen
Weight=75

*/ ?>
