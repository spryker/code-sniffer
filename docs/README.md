# Spryker Code Sniffer


The Spryker standard contains 134 sniffs

Generic (22 sniffs)
-------------------
- Generic.Arrays.DisallowLongArraySyntax
- Generic.CodeAnalysis.ForLoopShouldBeWhileLoop
- Generic.CodeAnalysis.ForLoopWithTestFunctionCall
- Generic.CodeAnalysis.JumbledIncrementer
- Generic.CodeAnalysis.UnconditionalIfStatement
- Generic.CodeAnalysis.UnnecessaryFinalModifier
- Generic.ControlStructures.InlineControlStructure
- Generic.Files.ByteOrderMark
- Generic.Files.LineEndings
- Generic.Files.LineLength
- Generic.Formatting.DisallowMultipleStatements
- Generic.Formatting.NoSpaceAfterCast
- Generic.Functions.FunctionCallArgumentSpacing
- Generic.NamingConventions.UpperCaseConstantName
- Generic.PHP.DeprecatedFunctions
- Generic.PHP.DisallowShortOpenTag
- Generic.PHP.ForbiddenFunctions
- Generic.PHP.LowerCaseConstant
- Generic.PHP.LowerCaseKeyword
- Generic.PHP.NoSilencedErrors
- Generic.WhiteSpace.DisallowTabIndent
- Generic.WhiteSpace.ScopeIndent

PEAR (4 sniffs)
---------------
- PEAR.Classes.ClassDeclaration
- PEAR.ControlStructures.ControlSignature
- PEAR.Functions.ValidDefaultValue
- PEAR.NamingConventions.ValidClassName

PSR1 (3 sniffs)
---------------
- PSR1.Classes.ClassDeclaration
- PSR1.Files.SideEffects
- PSR1.Methods.CamelCapsMethodName

PSR2 (12 sniffs)
----------------
- PSR2.Classes.ClassDeclaration
- PSR2.Classes.PropertyDeclaration
- PSR2.ControlStructures.ControlStructureSpacing
- PSR2.ControlStructures.ElseIfDeclaration
- PSR2.ControlStructures.SwitchDeclaration
- PSR2.Files.ClosingTag
- PSR2.Files.EndFileNewline
- PSR2.Methods.FunctionCallSignature
- PSR2.Methods.FunctionClosingBrace
- PSR2.Methods.MethodDeclaration
- PSR2.Namespaces.NamespaceDeclaration
- PSR2.Namespaces.UseDeclaration

SlevomatCodingStandard (6 sniffs)
---------------------------------
- SlevomatCodingStandard.Arrays.TrailingArrayComma
- SlevomatCodingStandard.Namespaces.AlphabeticallySortedUses
- SlevomatCodingStandard.Namespaces.UnusedUses
- SlevomatCodingStandard.Namespaces.UseFromSameNamespace
- SlevomatCodingStandard.TypeHints.ParameterTypeHintSpacing
- SlevomatCodingStandard.TypeHints.ReturnTypeHintSpacing

Spryker (61 sniffs)
-------------------
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
- Spryker.Commenting.SprykerAnnotation
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
- Spryker.Namespaces.SprykerNoCrossNamespace
- Spryker.Namespaces.SprykerNoPyz
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

Squiz (25 sniffs)
-----------------
- Squiz.Arrays.ArrayBracketSpacing
- Squiz.Classes.LowercaseClassKeywords
- Squiz.Classes.ValidClassName
- Squiz.Commenting.DocCommentAlignment
- Squiz.ControlStructures.ControlSignature
- Squiz.ControlStructures.ForEachLoopDeclaration
- Squiz.ControlStructures.ForLoopDeclaration
- Squiz.ControlStructures.LowercaseDeclaration
- Squiz.Functions.FunctionDeclaration
- Squiz.Functions.FunctionDeclarationArgumentSpacing
- Squiz.Functions.LowercaseFunctionKeywords
- Squiz.Functions.MultiLineFunctionDeclaration
- Squiz.Operators.ValidLogicalOperators
- Squiz.PHP.Eval
- Squiz.PHP.NonExecutableCode
- Squiz.Scope.MemberVarScope
- Squiz.Scope.MethodScope
- Squiz.Scope.StaticThisUsage
- Squiz.WhiteSpace.ControlStructureSpacing
- Squiz.WhiteSpace.LanguageConstructSpacing
- Squiz.WhiteSpace.LogicalOperatorSpacing
- Squiz.WhiteSpace.ScopeClosingBrace
- Squiz.WhiteSpace.ScopeKeywordSpacing
- Squiz.WhiteSpace.SemicolonSpacing
- Squiz.WhiteSpace.SuperfluousWhitespace

Zend (1 sniff)
---------------
- Zend.Files.ClosingTag;