# Spryker Code Sniffer


The Spryker standard contains 106 sniffs

Generic (15 sniffs)
-------------------
- Generic.Arrays.DisallowLongArraySyntax
- Generic.CodeAnalysis.ForLoopShouldBeWhileLoop
- Generic.CodeAnalysis.ForLoopWithTestFunctionCall
- Generic.CodeAnalysis.JumbledIncrementer
- Generic.CodeAnalysis.UnconditionalIfStatement
- Generic.CodeAnalysis.UnnecessaryFinalModifier
- Generic.Files.LineEndings
- Generic.Formatting.DisallowMultipleStatements
- Generic.Formatting.NoSpaceAfterCast
- Generic.PHP.DeprecatedFunctions
- Generic.PHP.ForbiddenFunctions
- Generic.PHP.LowerCaseConstant
- Generic.PHP.NoSilencedErrors
- Generic.WhiteSpace.DisallowTabIndent
- Generic.WhiteSpace.ScopeIndent

PEAR (3 sniffs)
---------------
- PEAR.ControlStructures.ControlSignature
- PEAR.Functions.ValidDefaultValue
- PEAR.NamingConventions.ValidClassName

PSR2 (7 sniffs)
---------------
- PSR2.ControlStructures.ControlStructureSpacing
- PSR2.ControlStructures.ElseIfDeclaration
- PSR2.ControlStructures.SwitchDeclaration
- PSR2.Files.EndFileNewline
- PSR2.Methods.FunctionCallSignature
- PSR2.Namespaces.NamespaceDeclaration
- PSR2.Namespaces.UseDeclaration

SlevomatCodingStandard (1 sniff)
---------------------------------
- SlevomatCodingStandard.Namespaces.UnusedUses

Spryker (61 sniffs)
-------------------
- Spryker.Classes.ClassDeclaration
- Spryker.Classes.ClassFileName
- Spryker.Classes.MethodArgumentDefaultValue
- Spryker.Classes.MethodDeclaration
- Spryker.Commenting.DocBlock
- Spryker.Commenting.DocBlockApiAnnotation
- Spryker.Commenting.DocBlockNoInlineAlignment
- Spryker.Commenting.DocBlockParam
- Spryker.Commenting.DocBlockParamAllowDefaultValue
- Spryker.Commenting.DocBlockParamArray
- Spryker.Commenting.DocBlockParamNotJustNull
- Spryker.Commenting.DocBlockPipeSpacing
- Spryker.Commenting.DocBlockReturnSelf
- Spryker.Commenting.DocBlockReturnTag
- Spryker.Commenting.DocBlockReturnVoid
- Spryker.Commenting.DocBlockTagGrouping
- Spryker.Commenting.DocBlockTagOrder
- Spryker.Commenting.DocBlockTestGroupAnnotation
- Spryker.Commenting.DocBlockTestGroupAnnotation2
- Spryker.Commenting.DocBlockThrows
- Spryker.Commenting.DocBlockVar
- Spryker.Commenting.FileDocBlock
- Spryker.Commenting.FullyQualifiedClassNameInDocBlock
- Spryker.Commenting.InlineDocBlock
- Spryker.Commenting.SprykerBridge
- Spryker.Commenting.SprykerFacade
- Spryker.ControlStructures.ConditionalExpressionOrder
- Spryker.ControlStructures.ControlStructureSpacing
- Spryker.ControlStructures.NoInlineAssignment
- Spryker.DependencyProvider.FacadeNotInBridgeReturned
- Spryker.Facade.FactoryMethodAnnotation
- Spryker.Factory.ConfigMethodAnnotation
- Spryker.Factory.CreateVsGetMethods
- Spryker.Factory.NoPrivateMethods
- Spryker.Factory.OneNewPerMethod
- Spryker.Factory.QueryContainerMethodAnnotation
- Spryker.Formatting.ArrayDeclaration
- Spryker.Internal.SprykerNoDemoshop
- Spryker.Namespaces.SprykerNamespace
- Spryker.Namespaces.SprykerNoPyz
- Spryker.Namespaces.UseInAlphabeticalOrder
- Spryker.Namespaces.UseStatement
- Spryker.Namespaces.UseWithAliasing
- Spryker.Namespaces.UseWithLeadingBackslash
- Spryker.PHP.NoIsNull
- Spryker.PHP.PhpSapiConstant
- Spryker.PHP.PreferCastOverFunction
- Spryker.PHP.RemoveFunctionAlias
- Spryker.PHP.ShortCast
- Spryker.Plugin.FacadeMethodAnnotation
- Spryker.Plugin.FactoryMethodAnnotation
- Spryker.WhiteSpace.CommaSpacing
- Spryker.WhiteSpace.ConcatenationSpacing
- Spryker.WhiteSpace.EmptyEnclosingLine
- Spryker.WhiteSpace.EmptyLines
- Spryker.WhiteSpace.FunctionSpacing
- Spryker.WhiteSpace.ImplicitCastSpacing
- Spryker.WhiteSpace.MemberVarSpacing
- Spryker.WhiteSpace.MethodSpacing
- Spryker.WhiteSpace.ObjectAttributeSpacing
- Spryker.WhiteSpace.OperatorSpacing

Squiz (18 sniffs)
-----------------
- Squiz.Arrays.ArrayBracketSpacing
- Squiz.Classes.LowercaseClassKeywords
- Squiz.Commenting.DocCommentAlignment
- Squiz.ControlStructures.ForEachLoopDeclaration
- Squiz.ControlStructures.LowercaseDeclaration
- Squiz.Functions.FunctionDeclaration
- Squiz.Functions.FunctionDeclarationArgumentSpacing
- Squiz.Functions.MultiLineFunctionDeclaration
- Squiz.Operators.ValidLogicalOperators
- Squiz.PHP.Eval
- Squiz.PHP.NonExecutableCode
- Squiz.Scope.MemberVarScope
- Squiz.Scope.MethodScope
- Squiz.Scope.StaticThisUsage
- Squiz.WhiteSpace.LanguageConstructSpacing
- Squiz.WhiteSpace.LogicalOperatorSpacing
- Squiz.WhiteSpace.SemicolonSpacing
- Squiz.WhiteSpace.SuperfluousWhitespace

Zend (1 sniff)
---------------
- Zend.Files.ClosingTag;