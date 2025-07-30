#define MyAppName "Faveo Agent"
#define MyAppVersion "2.8.0"
#define MyAppPublisher "Faveo"
#define MyAppURL "https://faveohelpdesk.com"
#define MyAppExeName "faveoagent.exe"
#define MESHEXE "meshagent.exe"
#define MESHDIR "{sd}\Program Files\Mesh Agent"

[Setup]
AppId={{0D34D278-5FAF-4159-A4A0-4E2D2C08139D}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppVerName={#MyAppName}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
AppUpdatesURL={#MyAppURL}
DefaultDirName="{sd}\Program Files\FaveoAgent"
DisableDirPage=yes
SetupLogging=yes
DisableProgramGroupPage=yes
SetupIconFile=iconFilePath
WizardSmallImageFile=logoFilePath
UninstallDisplayIcon={app}\{#MyAppExeName}
Compression=lzma
SolidCompression=yes
WizardStyle=modern
RestartApplications=no
CloseApplications=no
MinVersion=6.1
VersionInfoVersion=1.0.0.0
AppCopyright="Copyright (C) 2024 {#MyAppPublisher}"
OutputDir=outPutDir
OutputBaseFilename=outputFileName

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"

[Files]
Source: "binaryFilePath"; DestDir: "{app}"; Flags: ignoreversion;

[Run]
Filename: "{app}\{#MyAppExeName}"; Description: "{cm:LaunchProgram,{#StringChange(MyAppName, '&', '&&')}}"; Flags: nowait postinstall skipifsilent runascurrentuser

[UninstallRun]
Filename: "{app}\{#MyAppExeName}"; Parameters: "-m cleanup"; RunOnceId: "cleanuprm";
Filename: "{cmd}"; Parameters: "/c taskkill /F /IM faveoagent.exe"; RunOnceId: "killtacrmm";
Filename: "{app}\{#MESHEXE}"; Parameters: "-fulluninstall"; RunOnceId: "meshrm";

[UninstallDelete]
Type: filesandordirs; Name: "{app}";
Type: filesandordirs; Name: "{#MESHDIR}";

[Code]
function InitializeSetup(): boolean;
var
  ResultCode: Integer;
begin
  Exec('cmd.exe', '/c ping 127.0.0.1 -n 2 && net stop tacticalrpc', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Log('Stop tacticalrpc: ' + IntToStr(ResultCode));

  Exec('cmd.exe', '/c net stop faveoagent', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Log('Stop faveoagent: ' + IntToStr(ResultCode));

  Exec('cmd.exe', '/c ping 127.0.0.1 -n 2 && net stop faveoagent', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Log('Stop faveoagent: ' + IntToStr(ResultCode));

  Exec('cmd.exe', '/c taskkill /F /IM faveoagent.exe', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Log('taskkill: ' + IntToStr(ResultCode));

  Exec('cmd.exe', '/c sc delete faveoagent', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Log('delete faveoagent: ' + IntToStr(ResultCode));

  Exec('cmd.exe', '/c sc delete tacticalrpc', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Log('delete tacticalrpc: ' + IntToStr(ResultCode));

  Result := True;
end;

procedure DeinitializeSetup();
var
  ResultCode: Integer;
  WorkingDir:   String;
begin

  WorkingDir := ExpandConstant('{sd}\Program Files\FaveoAgent');
  Exec('cmd.exe', ' /c faveoagent.exe -m installsvc', WorkingDir, SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Log('install service: ' + IntToStr(ResultCode));

  Exec('cmd.exe', '/c net start faveoagent', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Log('Start faveoagent: ' + IntToStr(ResultCode));
end;

function InitializeUninstall(): Boolean;
var
  ResultCode: Integer;
begin
  Exec('cmd.exe', '/c ping 127.0.0.1 -n 2 && net stop faveoagent', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Exec('cmd.exe', '/c taskkill /F /IM faveoagent.exe', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);

  Exec('cmd.exe', '/c sc delete faveoagent', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
  Log('delete faveoagent: ' + IntToStr(ResultCode));

  Result := True;
end;
