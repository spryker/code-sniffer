# Spryker Code Sniffer


The SprykerStrict standard contains 197 sniffs

Generic (25 sniffs)
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
- Generic.PHP.DisallowAlternativePHPTags
- Generic.PHP.DisallowShortOpenTag
- Generic.PHP.ForbiddenFunctions
- Generic.PHP.LowerCaseConstant
- Generic.PHP.LowerCaseKeyword
- Generic.PHP.LowerCaseType
- Generic.PHP.NoSilencedErrors
- Generic.WhiteSpace.DisallowTabIndent
- Generic.WhiteSpace.IncrementDecrementSpacing
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

PSR12 (13 sniffs)
-----------------
- PSR12.Classes.AnonClassDeclaration
- PSR12.Classes.ClassInstantiation
- PSR12.Classes.ClosingBrace
- PSR12.ControlStructures.BooleanOperatorPlacement
- PSR12.ControlStructures.ControlStructureSpacing
- PSR12.Files.ImportStatement
- PSR12.Functions.NullableTypeDeclaration
- PSR12.Functions.ReturnTypeDeclaration
- PSR12.Keywords.ShortFormTypeKeywords
- PSR12.Namespaces.CompoundNamespaceDepth
- PSR12.Operators.OperatorSpacing
- PSR12.Properties.ConstantVisibility
- PSR12.Traits.UseDeclaration

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

SlevomatCodingStandard (25 sniffs)
----------------------------------
- SlevomatCodingStandard.Arrays.TrailingArrayComma
- SlevomatCodingStandard.Classes.ClassConstantVisibility
- SlevomatCodingStandard.Classes.ClassMemberSpacing
- SlevomatCodingStandard.Classes.ModernClassNameReference
- SlevomatCodingStandard.Commenting.DisallowOneLinePropertyDocComment
- SlevomatCodingStandard.Commenting.EmptyComment
- SlevomatCodingStandard.ControlStructures.DisallowContinueWithoutIntegerOperandInSwitch
- SlevomatCodingStandard.ControlStructures.DisallowYodaComparison
- SlevomatCodingStandard.ControlStructures.JumpStatementsSpacing
- SlevomatCodingStandard.ControlStructures.NewWithParentheses
- SlevomatCodingStandard.Exceptions.DeadCatch
- SlevomatCodingStandard.Namespaces.AlphabeticallySortedUses
- SlevomatCodingStandard.Namespaces.UnusedUses
- SlevomatCodingStandard.Namespaces.UseDoesNotStartWithBackslash
- SlevomatCodingStandard.Namespaces.UseFromSameNamespace
- SlevomatCodingStandard.Namespaces.UseSpacing
- SlevomatCodingStandard.PHP.ForbiddenClasses
- SlevomatCodingStandard.PHP.ShortList
- SlevomatCodingStandard.PHP.TypeCast
- SlevomatCodingStandard.PHP.UselessSemicolon
- SlevomatCodingStandard.TypeHints.LongTypeHints
- SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue
- SlevomatCodingStandard.TypeHints.ParameterTypeHintSpacing
- SlevomatCodingStandard.TypeHints.ReturnTypeHintSpacing
- SlevomatCodingStandard.Variables.DuplicateAssignmentToVariable

Spryker (85 sniffs)
-------------------
- Spryker.Classes.ClassFileName
- Spryker.Classes.MethodArgumentDefaultValue
- Spryker.Classes.MethodDeclaration
- Spryker.Classes.MethodTypeHint
- Spryker.Classes.PropertyDefaultValue
- Spryker.Classes.ReturnTypeHint
- Spryker.Commenting.DocBlock
- Spryker.Commenting.DocBlockApiAnnotation
- Spryker.Commenting.DocBlockConstructor
- Spryker.Commenting.DocBlockInherit
- Spryker.Commenting.DocBlockNoEmpty
- Spryker.Commenting.DocBlockNoInlineAlignment
- Spryker.Commenting.DocBlockParam
- Spryker.Commenting.DocBlockParamAllowDefaultValue
- Spryker.Commenting.DocBlockParamArray
- Spryker.Commenting.DocBlockParamNotJustNull
- Spryker.Commenting.DocBlockPipeSpacing
- Spryker.Commenting.DocBlockReturnNullableType
- Spryker.Commenting.DocBlockReturnSelf
- Spryker.Commenting.DocBlockReturnTag
- Spryker.Commenting.DocBlockReturnVoid
- Spryker.Commenting.DocBlockStructure
- Spryker.Commenting.DocBlockTag
- Spryker.Commenting.DocBlockTagGrouping
- Spryker.Commenting.DocBlockTagIterable
- Spryker.Commenting.DocBlockTagOrder
- Spryker.Commenting.DocBlockTestGroupAnnotation
- Spryker.Commenting.DocBlockTestGroupAnnotation2
- Spryker.Commenting.DocBlockThrows
- Spryker.Commenting.DocBlockTypeOrder
- Spryker.Commenting.DocBlockVar
- Spryker.Commenting.DocBlockVarNotJustNull
- Spryker.Commenting.DocBlockVariableNullHintLast
- Spryker.Commenting.DocComment
- Spryker.Commenting.FileDocBlock
- Spryker.Commenting.FullyQualifiedClassNameInDocBlock
- Spryker.Commenting.InlineDocBlock
- Spryker.Commenting.SprykerAnnotation
- Spryker.Commenting.SprykerBridge
- Spryker.Commenting.SprykerConstants
- Spryker.Commenting.SprykerFacade
- Spryker.ControlStructures.ConditionalExpressionOrder
- Spryker.ControlStructures.ControlStructureSpacing
- Spryker.ControlStructures.NoInlineAssignment
- Spryker.DependencyProvider.FacadeNotInBridgeReturned
- Spryker.Factory.CreateVsGetMethods
- Spryker.Factory.NoPrivateMethods
- Spryker.Factory.OneNewPerMethod
- Spryker.Formatting.ArrayDeclaration
- Spryker.Formatting.MethodSignatureParametersLineBreakMethod
- Spryker.Internal.SprykerNoDemoshop
- Spryker.MethodAnnotation.ConfigMethodAnnotation
- Spryker.MethodAnnotation.EntityManagerMethodAnnotation
- Spryker.MethodAnnotation.FacadeMethodAnnotation
- Spryker.MethodAnnotation.FactoryMethodAnnotation
- Spryker.MethodAnnotation.QueryContainerMethodAnnotation
- Spryker.MethodAnnotation.RepositoryMethodAnnotation
- Spryker.Namespaces.FunctionNamespace
- Spryker.Namespaces.SprykerNamespace
- Spryker.Namespaces.SprykerNoCrossNamespace
- Spryker.Namespaces.SprykerNoPyz
- Spryker.Namespaces.UseStatement
- Spryker.Namespaces.UseWithAliasing
- Spryker.PHP.DisallowFunctions
- Spryker.PHP.Exit
- Spryker.PHP.NoIsNull
- Spryker.PHP.NotEqual
- Spryker.PHP.PhpSapiConstant
- Spryker.PHP.PreferCastOverFunction
- Spryker.PHP.RemoveFunctionAlias
- Spryker.PHP.ShortCast
- Spryker.PHP.SingleQuote
- Spryker.Testing.Mock
- Spryker.WhiteSpace.CommaSpacing
- Spryker.WhiteSpace.ConcatenationSpacing
- Spryker.WhiteSpace.DocBlockSpacing
- Spryker.WhiteSpace.EmptyEnclosingLine
- Spryker.WhiteSpace.EmptyLines
- Spryker.WhiteSpace.FunctionSpacing
- Spryker.WhiteSpace.ImplicitCastSpacing
- Spryker.WhiteSpace.MemberVarSpacing
- Spryker.WhiteSpace.MethodSpacing
- Spryker.WhiteSpace.NamespaceSpacing
- Spryker.WhiteSpace.ObjectAttributeSpacing
- Spryker.WhiteSpace.OperatorSpacing

SprykerStrict (2 sniffs)
------------------------
- SprykerStrict.TypeHints.ParameterTypeHint
- SprykerStrict.TypeHints.PropertyTypeHint

Squiz (27 sniffs)
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
- Squiz.WhiteSpace.CastSpacing
- Squiz.WhiteSpace.ControlStructureSpacing
- Squiz.WhiteSpace.FunctionOpeningBraceSpace
- Squiz.WhiteSpace.LanguageConstructSpacing
- Squiz.WhiteSpace.LogicalOperatorSpacing
- Squiz.WhiteSpace.ScopeClosingBrace
- Squiz.WhiteSpace.ScopeKeywordSpacing
- Squiz.WhiteSpace.SemicolonSpacing
- Squiz.WhiteSpace.SuperfluousWhitespace

Zend (1 sniff)
---------------
- Zend.Files.ClosingTag
