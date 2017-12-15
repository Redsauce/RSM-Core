<?php

$definitions = array(
    'projects'                => 'projects',
    'projectName'             => 'projects.name',
    'projectDescription'      => 'projects.description',
    'projectStartDate'        => 'projects.startDate',
    'projectEndDate'          => 'projects.endDate',
    'projectStaff'            => 'projects.staff',
    'projectBudget'           => 'projects.budget',
    'projectClient'           => 'projects.clientID',
    'projectStatus'           => 'projects.status',

    'tasks'               => 'tasks',
    'taskName'            => 'tasks.name',
    'taskDescription'     => 'tasks.description',
    'taskProjectID'       => 'tasks.projectID',
    'taskParentID'        => 'tasks.parentID',
    'taskStartDate'       => 'tasks.startDate',
    'taskEndDate'         => 'tasks.endDate',
    'taskStaff'           => 'tasks.staff',
    'taskCurrentTime'     => 'tasks.currentTime',
    'taskTotalTime'       => 'tasks.totalTime',
    'taskStatus'          => 'tasks.status',

    'staff'                    => 'staff',
    'staffStatus'              => 'staff.status',
    'staffPayrollSubAccountID' => 'staff.payrollSubAccountID',
    'staffName'                => 'staff.name',
	'staffTaxCode'             => 'staff.taxCode',
	'staffBankAccount'         => 'staff.bankAccount',
	'staffBIC'                 => 'staff.bic',
	'staffAddress'             => 'staff.address',
	'staffPostCode'            => 'staff.postCode',
	'staffCity'                => 'staff.city',

    'subAccounts'             => 'subAccounts',
    'subAccountName'          => 'subAccounts.name',
    'subAccountPersonalID'    => 'subAccounts.personalID',
    'subAccountShortName'     => 'subAccounts.shortName',
    'subAccountAccountID'     => 'subAccounts.accountID',
    'subAccountTaxCode'       => 'subAccounts.taxCode',
    'subAccountAddress'       => 'subAccounts.address',
    'subAccountPostCode'      => 'subAccounts.postCode',
    'subAccountCity'          => 'subAccounts.city',
    'subAccountCountry'       => 'subAccounts.country',
    'subAccountPhone'         => 'subAccounts.phone',
    'subAccountFax'           => 'subAccounts.fax',
    'subAccountWebsite'       => 'subAccounts.website',
    'subAccountEmail'         => 'subAccounts.email',
    'subAccountBankAccount'   => 'subAccounts.bankAccount',

    'worksessions'            => 'worksessions',
    'worksessionStartDate'    => 'worksessions.startDate',
    'worksessionDuration'     => 'worksessions.duration',
    'worksessionTask'         => 'worksessions.task',
    'worksessionUser'         => 'worksessions.user',
    'worksessionDescription'  => 'worksessions.description',
    'worksessionCreationDate' => 'worksessions.creationDate',

    'expenses'                => 'expenses',
    'expenseCreationDate'     => 'expenses.creationDate',
    'expenseDate'             => 'expenses.date',
    'expensePayDate'          => 'expenses.payDate',
    'expenseType'             => 'expenses.type',
    'expenseUser'             => 'expenses.user',
    'expenseProject'          => 'expenses.project',
    'expenseAmount'           => 'expenses.amount',
    'expenseConcept'          => 'expenses.concept',

    'stockItemGroup'          => 'stockItemGroup',
    'stockItemGroupName'      => 'stockItemGroup.name',
    'stockItemGroupParent'    => 'stockItemGroup.parent',

    'stockItem'            => 'stockItem',
    'stockItemName'        => 'stockItem.name',
    'stockItemParentGroup' => 'stockItem.parentGroup',
    'stockItemSalePrice'   => 'stockItem.salePrice',
    'stockItemIdentifier'  => 'stockItem.identifier',
    'stockItemAmount'      => 'stockItem.amount',
    'stockItemAmountSold'  => 'stockItem.amountSold',

    'pendingStock'            => 'pendingStock',
    'pendingStockOperationID' => 'pendingStock.operationID',
    'pendingStockItemID'      => 'pendingStock.itemID',
    'pendingStockAmount'      => 'pendingStock.amount',

    'accounts'            => 'accounts',
    'accountName'         => 'accounts.name',
    'accountType'         => 'accounts.type',

    'operations'                 => 'operations',
    'operationSubAccountID'      => 'operations.subAccountID',
    'operationOperationID'       => 'operations.operationID',
    'operationSendDate'          => 'operations.sendDate',
    'operationPayDate'           => 'operations.payDate',
    'operationInvoiceDate'       => 'operations.invoiceDate',
    'operationValueDate'         => 'operations.valueDate',
    'operationDomicilyDate'      => 'operations.domicilyDate',
    'operationBase'              => 'operations.base',
    'operationIVA'               => 'operations.IVA',
    'operationDeduction'         => 'operations.deduction',
    'operationTotal'             => 'operations.total',
    'operationDescription'       => 'operations.description',
    'operationPayMethod'         => 'operations.payMethod',
    'operationBankAccount'       => 'operations.bankAccount',
    'operationNote'              => 'operations.note',
    'operationShowNote'          => 'operations.showNote',
    'operationStatus'            => 'operations.status',
    'operationCashID'            => 'operations.cashID',

    'concepts'              => 'concepts',
    'conceptName'           => 'concepts.name',
    'conceptProjectID'      => 'concepts.projectID',
    'conceptUnits'          => 'concepts.units',
    'conceptIVA'            => 'concepts.IVA',
    'conceptPrice'          => 'concepts.price',
    'conceptDeduction'      => 'concepts.deduction',
    'conceptOperationID'    => 'concepts.operationID',
    'conceptOrden'          => 'concepts.orden',
    'conceptStockItemID'    => 'concepts.stockItemID',
    'conceptPendingStockID' => 'concepts.pendingStockID',

    'studies'               => 'studies',
    'studiesName'           => 'studies.name',
    'studiesStatus'         => 'studies.status',

    'groups'                => 'groups',
    'groupsName'            => 'groups.name',
    'groupsStudyID'         => 'groups.associatedStudy',

    'testcasescategory'         => 'testcasescategory',
    'testcasescategoryName'     => 'testcasescategory.name',
    'testcasescategoryParentID' => 'testcasescategory.parentID',
    'testcasescategoryGroupID'  => 'testcasescategory.groupID',
    'testcasescategoryOrder'    => 'testcasescategory.order',

    'testcases'             => 'testcases',
    'testcasesName'         => 'testcases.name',
    'testcasesFolderID'     => 'testcases.parentFolderID',
    'testcasesSubjects'     => 'testcases.subjects',
    'testcasesOrder'        => 'testcases.order',

    'steps'                       => 'steps',
    'stepsName'                   => 'steps.name',
    'stepsTestCaseParentID'       => 'steps.testCaseParentID',
    'stepsOrder'                  => 'steps.order',
    'stepsCheckedStepUnits'       => 'steps.CheckedStepUnits',
    'stepsDescription'            => 'steps.description',
    'stepsRelatedID'              => 'steps.relatedID',
    'stepsRoundSubjectRelationID' => 'steps.roundSubjectRelationID',
    'stepsType'                   => 'steps.type',

    'stepUnits'                => 'stepUnits',
    'stepUnitsName'            => 'stepUnits.name',
    'stepUnitsStepParentID'    => 'stepUnits.stepParentID',
    'stepUnitsUnit'            => 'stepUnits.unit',
    'stepUnitsConversionValue' => 'stepUnits.valueConversion',
    'stepUnitsParentStudy'     => 'stepUnits.parentStudyID',
    'stepUnitsIsGlobal'        => 'stepUnits.isGlobal',
    'stepUnitsValuesList'      => 'stepUnits.valuesList',

    'orderUnits'                => 'orderUnits',
    'orderUnitsUnitID'          => 'orderUnits.UnitID',
    'orderUnitsStepID'          => 'orderUnits.StepID',
    'orderUnitsOrder'           => 'orderUnits.order',

    'roundsplanning'                  => 'roundsplanning',
    'roundsplanningName'              => 'roundsplanning.name',
    'roundsplanningOrder'             => 'roundsplanning.order',
    'roundsplanningAssociatedStudyID' => 'roundsplanning.associatedStudyID',

    'subject'                     => 'subject',
    'subjectFirstExecution'       => 'subject.firstExecution',
    'subjectLastExecution'        => 'subject.lastExecution',
    'subjectStudyID'              => 'subject.studyID',

    'result'                     => 'result',
    'resultStepUnitAssociatedID' => 'result.StepUnitID',
    'resultSubjectAssociatedID'  => 'result.SubjectID',
    'resultStepAssociatedID'     => 'result.StepID',
    'resultValue'                => 'result.value',

	  'ticket'                     => 'ticket',
	  'ticketID'                   => 'ticket.ID',
	  'ticketDate'                 => 'ticket.date',

    'cashRegisters'                          => 'cashRegisters',
    'cashRegisterID'                         => 'cashRegister.ID',
    'cashRegisterCashSubAccountID'           => 'cashRegister.CashSubAccountID',
    'cashRegisterSalesSubAccountID'          => 'cashRegister.SalesSubAccountID',
    'cashRegisterLossesSubAccountID'         => 'cashRegister.LossesSubAccountID',
    'cashRegisterEmptyClientSubAccountID'    => 'cashRegister.EmptyClientSubAccountID',
    'cashRegisterRemainder'                  => 'cashRegister.Remainder',
    'cashRegisterMACAddress'                 => 'cashRegister.MACAddress',
    'cashRegisterLastClose'                  => 'cashRegister.LastClose',
    'cashRegisterCashPaymentMethod'          => 'cashRegister.CashPaymentMethod',
    'cashRegisterClientSubAccountAccountID'  => 'cashRegister.ClientSubAccountAccountID',
    'cashRegisterPrinterPort'                => 'cashRegister.printerPort',
    'cashRegisterPrinterBaud'                => 'cashRegister.printerBaud',
    'cashRegisterPrinterParity'              => 'cashRegister.printerParity',
    'cashRegisterPrinterBits'                => 'cashRegister.printerBits',
    'cashRegisterPrinterStop'                => 'cashRegister.printerStop',
    'cashRegisterFinancialInvoiceDocumentID' => 'cashRegister.financial.invoiceDocumentID',
    'cashRegisterFinancialTicketDocumentID'  => 'cashRegister.financial.ticketDocumentID',

    'cashLog'                   => 'cashLog',
    'cashLogCashRegisterID'     => 'cashLog.cashRegisterID',
    'cashLogOperation'          => 'cashLog.operation',
    'cashLogAmount'             => 'cashLog.amount',
    'cashLogDate'               => 'cashLog.date',
    'cashLogUserID'             => 'cashLog.userID',

    'client'                => 'client',
    'clientAddress'         => 'client.address',
    'clientBankAccount'     => 'client.bankAccount',
    'clientCity'            => 'client.city',
    'clientCountry'         => 'client.country',
    'clientEmail'           => 'client.email',
    'clientFax'             => 'client.fax',
    'clientName'            => 'client.name',
    'clientID'              => 'client.id',
    'clientPhone'           => 'client.phone',
    'clientPostCode'        => 'client.postCode',
    'clientShortName'       => 'client.shortName',
    'clientFiscalNumber'    => 'client.fiscalNumber',
    'clientWebsite'         => 'client.website',

    'financialDocuments'                      => 'financial.documents',
    'financialDocumentsItemTypeID'            => 'financial.documents.itemTypeID',
    'financialDocumentsConceptID'             => 'financial.documents.conceptID',
    'financialDocumentsSubItemTypeID'         => 'financial.documents.subItemTypeID',
    'financialDocumentsFilterCriteria'        => 'financial.documents.filterCriteria',
    'financialDocumentsConceptFilterCriteria' => 'financial.documents.conceptFilterCriteria',
    'financialDocumentsRelatedOperationIDs'   => 'financial.documents.relatedOperationIDs',
    'financialDocumentsPreviewURL'            => 'financial.documents.previewURL',
    'financialDocumentsUniqueID  '            => 'financial.documents.uniqueID',
    'financialDocumentsDate'                  => 'financial.documents.date',
    'financialDocumentsBase'                  => 'financial.documents.base',
    'financialDocumentsVAT'                   => 'financial.documents.vat',
    'financialDocumentsRET'                   => 'financial.documents.ret',
    'financialDocumentsTotal'                 => 'financial.documents.total',
    'financialDocumentsDescription'           => 'financial.documents.description',
    'financialDocumentsConceptVAT'           => 'financial.documents.concepts.vat',
    'financialDocumentsConceptRET'           => 'financial.documents.concepts.ret',
    'financialDocumentsConceptBase'          => 'financial.documents.concepts.base',
    'financialDocumentsConceptDescription'   => 'financial.documents.concepts.description',
    'financialDocumentsConceptProject'       => 'financial.documents.concepts.project',
    'financialDocumentsConceptStock'         => 'financial.documents.concepts.stock',
    'financialDocumentsConceptUnits'         => 'financial.documents.concepts.units',
    'financialDocumentsConceptOrder'         => 'financial.documents.concepts.order',

    'provider'             => 'provider',
    'providerAddress'      => 'provider.address',
    'providerBankAccount'  => 'provider.bankAccount',
    'providerCity'         => 'provider.city',
    'providerCountry'      => 'provider.country',
    'providerEmail'        => 'provider.email',
    'providerFax'          => 'provider.fax',
    'providerName'         => 'provider.name',
    'providerID'           => 'provider.id',
    'providerPhone'        => 'provider.phone',
    'providerPostCode'     => 'provider.postCode',
    'providerShortName'    => 'provider.shortName',
    'providerFiscalNumber' => 'provider.fiscalNumber',
    'providerWebsite'      => 'provider.website',

    'roundSubjectsTestRelations'           => 'roundSubjectsTestRelations',
    'roundSubjectsTestRelationsRoundID'    => 'roundSubjectsTestRelations.roundID',
    'roundSubjectsTestRelationsSubjectID'  => 'roundSubjectsTestRelations.subjectID',
    'roundSubjectsTestRelationsTestID'     => 'roundSubjectsTestRelations.testIDs',
    'roundSubjectsTestRelationsTestCatIDs' => 'roundSubjectsTestRelations.testCategoryIDs',

    'invoiceClient'                        => 'invoice.client',
    'invoiceClientClientID'                => 'invoice.client.clientID',
    'invoiceClientInvoiceID'               => 'invoice.client.invoiceID',
    'invoiceClientInvoiceDate'             => 'invoice.client.invoiceDate',
    'invoiceClientSentDate'                => 'invoice.client.sentDate',
    'invoiceClientPaymentDate'             => 'invoice.client.paymentDate',
    'invoiceClientDebitDate'               => 'invoice.client.debitDate',
    'invoiceClientDescription'             => 'invoice.client.description',
    'invoiceClientBase'                    => 'invoice.client.base',
    'invoiceClientVat'                     => 'invoice.client.vat',
    'invoiceClientRetention'               => 'invoice.client.retention',
    'invoiceClientTotal'                   => 'invoice.client.total',
    'invoiceClientBillingAddress'          => 'invoice.client.billingAddress',
    'invoiceClientBillingCity'             => 'invoice.client.billingCity',
    'invoiceClientBillingCountry'          => 'invoice.client.billingCountry',
    'invoiceClientBillingPostCode'         => 'invoice.client.billingPostCode',
    'invoiceClientBillingProvince'         => 'invoice.client.billingProvince',

    'payroll'                      => 'payroll',
    'payrollTotal'                 => 'payroll.total',
    'payrollDescription'           => 'payroll.description',
    'payrollStaffID'               => 'payroll.staffID',
    'payrollDate'                  => 'payroll.date',

    'testingExecution'          => 'testing.execution',
    'testingExecutionName'      => 'testing.execution.name',
    'testingExecutionSubjectID' => 'testing.execution.subjectID',
    'testingExecutionRoundID'   => 'testing.execution.roundID',

    'configurationModuleGeneric'            => 'configuration.module.generic',
    'configurationModuleGenericName'        => 'configuration.module.generic.name',
    'configurationModuleGenericDescription' => 'configuration.module.generic.description',
    'configurationModuleGenericLogo'        => 'configuration.module.generic.logo',

    'configurationGenericModule'            => 'configuration.module.genericModule',
    'configurationGenericModuleName'        => 'configuration.module.genericModule.name',
    'configurationGenericModuleDescription' => 'configuration.module.genericModule.description',
    'configurationGenericModuleLogo'        => 'configuration.module.genericModule.logo',
    'configurationGenericModuleAllowed'     => 'configuration.module.genericModule.allowed',
    'configurationGenericModuleBase'        => 'configuration.module.genericModule.base',

    'configurationHTMLModule'            => 'configuration.module.HTMLModule',
    'configurationHTMLModuleName'        => 'configuration.module.HTMLModule.name',
    'configurationHTMLModuleDescription' => 'configuration.module.HTMLModule.description',
    'configurationHTMLModuleLogo'        => 'configuration.module.HTMLModule.logo',
    'configurationHTMLModuleURL'         => 'configuration.module.HTMLModule.URL',
    'configurationHTMLModuleOpening'  => 'configuration.module.HTMLModule.opening',

    'revisionHistory'                => 'revisionHistory',
    'revisionHistoryVersion'         => 'revisionHistory.version',

    'revisionHistoryRevision'        => 'revisionHistory.revision',
    'revisionHistoryAffectedModules' => 'revisionHistory.affectedModules',
    'revisionHistoryDescriptionES'   => 'revisionHistory.description.ES',
    'revisionHistoryDescriptionEN'   => 'revisionHistory.description.EN',
    'revisionHistoryDescriptionDE'   => 'revisionHistory.description.DE',

    'productBuild'        => 'productBuild',
    'productBuildProduct' => 'productBuild.product',

    'requirementsCategory' => 'requirementsCategory',
    'useCasesCategory'     => 'useCasesCategory',

    'useCases'             => 'useCases',

    'onlineStore'          => 'onlineStore',
    'onlineStore.URL'      => 'onlineStore.URL',

    'onlineStoreCategory'          => 'onlineStoreCategory',
    'onlineStoreCategory.StoreID'  => 'onlineStoreCategory.storeID',

    'onlineStoreProduct'                  => 'onlineStoreProduct',
    'onlineStoreProduct.CategoryIDs'      => 'onlineStoreProduct.categoryIDs',
    'onlineStoreAttribute'                => 'onlineStoreAttribute',
    'onlineStoreAttribute.StoreProductID' => 'onlineStoreAttribute.storeProductID',
    'onlineStoreAttribute.StockItemID'    => 'onlineStoreAttribute.stockItemID',
    'onlineStoreAttribute.Price'          => 'onlineStoreAttribute.price',

    'crmAdresses'          	=> 'crmAdresses',
    'crmAdresses.address'	=> 'crmAdresses.address',
    'crmAdresses.postcode'  => 'crmAdresses.postcode',
    'crmAdresses.city'		=> 'crmAdresses.city',
    'crmAdresses.province'	=> 'crmAdresses.province',
    'crmAdresses.country'	=> 'crmAdresses.country',

    'crmAccounts'                         => 'crmAccounts',
    'crmAccountsInvoicingCCC'             => 'crmAccounts.invoicing.ccc',
    'crmAccountsInvoicingName'            => 'crmAccounts.invoicing.name',
    'crmAccountsDefaultDeliveryAddress'   => 'crmAccounts.default.delivery.address',
    'crmAccountsDefaultInvoiceAddress'    => 'crmAccounts.default.invoice.address',


    'crmContacts'      => 'crmContacts',
    'crmOpportunities' => 'crmOpportunities',

    'catalogCategory'         => 'catalogCategory',
    'catalogCategoryParentID' => 'catalogCategory.parentID',

    'catalog'                 => 'catalog',
    'catalogParentCategoryID' => 'catalog.parentCategoryID',

    'catalogItem'             => 'catalogItem',
    'catalogItemParentID'     => 'catalogItem.parentID',
    'catalogItemName'         => 'catalogItem.name',
    'catalogItemPrice'        => 'catalogItem.price',

    'eventCategory'           => 'eventCategory',
    'eventCategoryParentID'   => 'eventCategory.parentID',

    'event'                  => 'event',
    'eventName'              => 'event.name',
    'eventActions'           => 'event.actions',
    'eventToken'             => 'event.token',
    'eventParentCategoryID'  => 'event.parentCategoryID',

    'eventInclude'         => 'eventInclude',
    'eventIncludeActions'  => 'eventInclude.actions',
    'eventIncludeEventIDs' => 'eventInclude.eventIDs',

    'eventTrigger'      => 'eventTrigger',
    'eventTriggerEvent' => 'eventTrigger.eventID',
    'eventTriggerType'  => 'eventTrigger.type',
    'eventTriggerData'  => 'eventTrigger.data',

    'bankAccount'                   => 'bankAccount',
    'bankAccount.IBAN.Country'      => 'bankAccount.IBAN.Country',
    'bankAccount.IBAN.CheckDigit'   => 'bankAccount.IBAN.CheckDigit',
    'bankAccount.IBAN.Bank'         => 'bankAccount.IBAN.Bank',
    'bankAccount.IBAN.Office'       => 'bankAccount.IBAN.Office',
    'bankAccount.IBAN.ControlDigit' => 'bankAccount.IBAN.ControlDigit',
    'bankAccount.IBAN.Account'      => 'bankAccount.IBAN.Account',
    'bankAccount.IBAN.SWIFT'        => 'bankAccount.IBAN.SWIFT',

    'newsType'             => 'newsType',
    'newsType.title'       => 'newsType.title',
    'newsType.description' => 'newsType.description',
    'newsType.URL'         => 'newsType.URL',
    'newsType.language'    => 'newsType.language',

    'news' 			   	=> 'news',
    'news.title'       	=> 'news.title',
    'news.description' 	=> 'news.description',
    'news.type' 		=> 'news.type',
    'news.author' 		=> 'news.author',
    'news.date' 		=> 'news.date',
    'news.image' 		=> 'news.image',
    'news.URL'          => 'news.URL',

    'tasksGroup'                 => 'tasksGroup',
    'tasksGroup.name'            => 'tasksGroup.name',
    'tasksGroup.projectID'       => 'tasksGroup.projectID',
    'tasksGroup.parentID'        => 'tasksGroup.parentID',
    'tasksGroup.startDate'       => 'tasksGroup.startDate',
    'tasksGroup.endDate'         => 'tasksGroup.endDate',
    'tasksGroup.staff'           => 'tasksGroup.staff',
    'tasksGroup.currentTime'     => 'tasksGroup.currentTime',
    'tasksGroup.totalTime'       => 'tasksGroup.totalTime',
    'tasksGroup.status'          => 'tasksGroup.status'
);

?>