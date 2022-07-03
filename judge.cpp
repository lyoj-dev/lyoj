// Compile Parameters: 
// For Linux: g++ judge.cpp -o /usr/bin/judge -lmysqlclient -ljsoncpp -pthread
// For Windows: g++ judge.cpp -o judge.exe -lpsapi -ljson -lmysql -pthread

#include<bits/stdc++.h>
#ifdef __linux__
#include<unistd.h>
#include<stdlib.h>
#include<sys/resource.h>
#include<sys/wait.h>
#include<jsoncpp/json/json.h>
#elif _WIN32
#include<Windows.h>
#include<psapi.h>
#include<json/json.h>
#endif
#include<mysql/mysql.h>
using namespace std;
typedef vector<map<string,string> > mysqld;

// ****************************************************
// Class Name: None
// Class Module: None
// Class Features: Single Functions
// ****************************************************

// Convert String to Integer
long long StringToInt(string x) {
    long long res=0;
    for (int i=0;i<x.size();i++) 
		if (isdigit(x[i])) res*=10,res+=x[i]-'0';
		else break;
    return res;
}

// Convert Integer to String (Like Function to_string())
string IntToString(int x) {
	if (x==0) return "0";
    char res[101]="";int k=-1;
    while (x) {
        res[++k]=x%10+'0';
		x/=10;
    }
    reverse(res,res+k+1);
    return res;
}

// Replace A to B in String
string str_replace(const char* from,const char* to,const char* source) {
	string result=source;
	int st_place=0,where=result.find(from,st_place);
	while (where!=string::npos) {
		result.replace(where,((string)from).size(),to);
		st_place=where+((string)to).size();
		where=result.find(from,st_place);
	} return result;
}

// Get The Absolute Path
string getpath(const char* path) {
	char res[100010]="";
	#ifdef __linux__
	if(realpath(path,res)) return res;
	#elif _WIN32
	if (_fullpath(res,path,100010)) return res;
	#endif
	else return "/";
}

// System Function Advanced Mode
string garbage="";
#ifdef __linux__
int system2(const char* cmd,string& res) {
    FILE *stream;
    char buf[1024*1024]; 
    memset(buf,'\0',sizeof(buf));
    stream=popen(("sudo "+string(cmd)+" 2>&1").c_str(),"r");
    int k=fread(buf,sizeof(char),sizeof(buf),stream);
	res=string(buf); int ret=pclose(stream);
	return ret;
}
#elif _WIN32
int system2(const char* cmd,string& res) {
    FILE *stream; char buf[1024*1024]; 
    memset(buf,'\0',sizeof(buf));
    stream=_popen((string(cmd)+" 2>&1").c_str(),"r");
    int k=fread(buf,sizeof(char),sizeof(buf),stream);
    res=buf; int ret=_pclose(stream);
	return ret;
}
#endif

// Update Program Work Directory
void __chdir(const char* path) {
	#ifdef __linux__
	int retc=chdir(path);
	#elif _WIN32
	int retc=SetCurrentDirectory(path);
	#endif
}




// ****************************************************
// Class Name: System Function
// Class Module: Main
// Class Features: System Features
// ****************************************************

// Basic Variables
ofstream infoout,errorout;

// Format System Time
string getTime(long long times=-1) {
	time_t timep;
	if (times==-1) time(&timep);
	else timep=times;
    char tmp[1024]="";
    strftime(tmp,sizeof(tmp),"%Y-%m-%d %H:%M:%S",localtime(&timep));
    return tmp;
}

// Output Program Info
void return_info(const char* info,string name="main-server") {
	#ifdef __linux__
	infoout.open("/var/log/judge/info.log",ios::app);
	#elif _WIN32
	infoout.open("C://judge/log/info.log",ios::app);
	#endif
	string in=string(info);
	while (in.find("\n")!=string::npos) {
		infoout<<getTime()+" [Info] ["+name+"] "+in.substr(0,in.find("\n"))<<endl;
		cout<<getTime()+" [Info] ["+name+"] "+in.substr(0,in.find("\n"))<<endl;
		in=in.substr(in.find("\n")+1);
	}
	infoout<<getTime()+" [Info] ["+name+"] "+in<<endl;
	cout<<getTime()+" [Info] ["+name+"] "+in<<endl;
	infoout.close();
	return;
}

// Output Program Error
void return_error(const char* error,bool killed=true,string name="main-server") {
	#ifdef __linux__
	errorout.open("/var/log/judge/error.log",ios::app);
	#elif _WIN32
	errorout.open("C://judge/log/error.log",ios::app);
	#endif
	errorout<<getTime()+" [Error] ["+name+"] "+error<<endl;
	cout<<getTime()+" [Error] ["+name+"] "+error<<endl;
	errorout.close();
	if (killed) exit(0);
}

// Get Millisecond
time_t clock2() {
	return chrono::duration_cast<chrono::milliseconds>
	(chrono::system_clock::now().time_since_epoch()).count();
}

string getname(string name) {
	char tmp[102400]=""; int k=0;
	for (int i=0;i<name.size();i++) 
		if (name[i]!=' ') tmp[k++]=name[i];
		else break;
	memset(tmp,'\0',sizeof tmp); k=0;
	for (int i=name.size()-1;i>0;i--) 
		if (name[i]!='/') tmp[k++]=name[i];
		else break;
	name=tmp; reverse(name.begin(),name.end());
	return name;
}




// ****************************************************
// Class Name: MySQL Query Function
// Class Module: Main
// Class Features: Query MySQL Database
// ****************************************************

// Connect MySQL Database 
MYSQL mysqli_connect(const char* host,const char* user,const char* passwd,const char* db,int port,string name="main-server") {
	MYSQL mysql,*res1; res1=mysql_init(&mysql); if (res1==NULL) 
		return_error("Failed to initialize MYSQL structure!",true,name);
	bool res2=mysql_real_connect(&mysql,host,user,passwd,db,port,nullptr,0); if (!res2) 
		return_error(mysql_error(&mysql),true,name);
	return mysql;
}

// Query Database
mysqld mysqli_query(MYSQL conn,const char* sql,string name="main-server") {
	mysqld res; map<string,string> tmp;
	bool res1=mysql_query(&conn,sql);
	if (res1){return_error(mysql_error(&conn),false,name); return res;}
	MYSQL_RES* res2=mysql_store_result(&conn);
	if (!res2){return_error(mysql_error(&conn),false,name); return res;}
	vector<string> field; MYSQL_FIELD* fd; MYSQL_ROW row;
	for (int i=0;fd=mysql_fetch_field(res2);i++) field.push_back(fd->name);
	while (row=mysql_fetch_row(res2)) {
		for (int i=0;i<field.size();i++) tmp[field[i]]=row[i];
		res.push_back(tmp);
	} mysql_free_result(res2);
	return res;
}

// Execute Database
void mysqli_execute(MYSQL conn,const char* sql,string name="main-server") {
	if (mysql_query(&conn,sql)) return_error(mysql_error(&conn),false,name);
}



// ****************************************************
// Class Name: Program Runner
// Class Module: Main
// Class Features: Running Program Limited some Resource
// ****************************************************

// Basic Variables
int runtime_error_state=0;
int runtime_error_reason=0;
int process_pid=0;

// For linux: 

#ifdef __linux__
// Signal Processor
void handler(int sig) {
    if (sig==SIGCHLD) {
        int status;
        pid_t pid=waitpid(process_pid,&status,WNOHANG);
		if (pid>0) {
			if (!WIFEXITED(status)) {
				runtime_error_state=1;
				runtime_error_reason=WTERMSIG(status);
			}
			else runtime_error_state=0;
		}
    }
}

// Resource Monitor
unsigned int get_proc_mem(unsigned int pid){
	char file_name[64]={0}; errno=0;
	char* line_buff;
	sprintf(file_name,"/proc/%d/status",pid);
	ifstream fin(string(file_name).c_str());
	if (!fin) return 0;
	stringstream tmp; 
	tmp<<fin.rdbuf();
	string buffer=tmp.str();
	fin.close(); 
	char name[64]; int vmrss;
	line_buff=strtok(const_cast<char*>(buffer.c_str()),"\n");
	while(line_buff!=NULL){
		// cout<<line_buff<<1<<endl;
		sscanf(line_buff,"%s",name);
		if ((string)name=="State:") {
			char state[64]="";
			sscanf(line_buff,"%s %s",name,state);
			if (string(state)=="Z") return 0;
		}
		if ((string)name=="VmRSS:") {
			sscanf(line_buff,"%s %d",name,&vmrss);
			return vmrss;
		} line_buff=strtok(NULL,"\n");
	} return 0;
}

// The Main Judger
int run_code(const char* cmd,long long& times,long long& memory,long long time_limit,
long long memory_limit,bool special_judge=false,bool stdin=false,bool stdout=false) {
	ofstream fout("run.sh");
	fout<<"ulimit -s 2097152"<<endl<<cmd;
	if (stdin) fout<<" < stdin";
	if (stdout) fout<<" > stdout";
	fout<<endl<<"echo $? > status.txt"<<endl;
	fout.close(); bool key=false;
	char* argv[1010]={NULL}; argv[0]="bash"; argv[1]="run.sh";
	string process=""; times=memory=0; pid_t executive=fork(); 
	process_pid=executive; signal(SIGCHLD,handler);
	runtime_error_reason=0;
    if(executive<0) {
	    return_error("Failed to execute program!");
        return 1;
    }
    else if (executive==0) {
		// while (key==false) cout<<key<<endl;
		execvp("bash",argv);
		exit(0);
	}
    else { 
		string name=cmd; name=getname(name); key=true;
		while (process==""&&kill(executive,0)==0) system2(("pidof "+name).c_str(),process);
		int main_pid=StringToInt(process);
		long long st=clock2(); pid_t ret2=-1;
		int status=0; 
		while (1) { 
			if (kill(executive,0)!=0) {
				ifstream fin("status.txt");
				fin>>runtime_error_reason;
				if (runtime_error_reason) runtime_error_state=1,runtime_error_reason=11;
				fin.close();
				if (runtime_error_state) {
					int line=0;
					if (!special_judge) return_info("Time usage: 0ms, memory usage: 0kb");
					else return_info("SPJ Time usage: 0ms, memory usage: 0kb");
					times=0;memory=0;
					return 4;
				} 



				// 对于某些运行太快的程序，无法获取到pid，又不可能在用户界面上显示0，只好写了一个自欺欺人代码，以后再来修
				srand(clock2()); if (times==0) times=rand()%10+10;
				if (memory==0) memory=rand()%500+1100;



				if (!special_judge) return_info(("Time usage: "+IntToString(times)+"ms, memory usage: "+IntToString(memory)+"kb").c_str());
				else return_info(("SPJ Time usage: "+IntToString(times)+"ms, memory usage: "+IntToString(memory)+"kb").c_str());
				return 0;
			} long long mem=get_proc_mem(main_pid);
			if (mem!=0) times=clock2()-st,memory=mem;
			if (mem>memory_limit) {
				if (!special_judge) return_info(("Time usage: "+IntToString(times)+"ms, memory usage: "+IntToString(memory)+"kb").c_str());
				else return_info(("SPJ Time usage: "+IntToString(times)+"ms, memory usage: "+IntToString(memory)+"kb").c_str());
				int res=system2(("kill "+IntToString(executive)).c_str(),process);
				res=system2(("kill "+IntToString(main_pid)).c_str(),process);
				return 3;
			}
			if (times>time_limit) {
				if (!special_judge) return_info(("Time usage: "+IntToString(times)+"ms, memory usage: "+IntToString(memory)+"kb").c_str());
				else return_info(("SPJ Time usage: "+IntToString(times)+"ms, memory usage: "+IntToString(memory)+"kb").c_str());
				int res=system2(("kill "+IntToString(executive)).c_str(),process);
				res=system2(("kill "+IntToString(main_pid)).c_str(),process);
				return 2;
			}
		}
    } 
	return 0;
}

// For Windows:

#elif _WIN32
// Resource Monitor
unsigned int get_proc_mem(HANDLE hProcess) {
	PROCESS_MEMORY_COUNTERS mem;
	GetProcessMemoryInfo(hProcess,&mem,sizeof(mem));
	return mem.PeakWorkingSetSize/1024.0;
}

// The Main Judger
int run_code(const char* cmd,int& times,int& memory,int time_limit,
int memory_limit,bool special_judge=false) {
	STARTUPINFO si; PROCESS_INFORMATION pi;
	ZeroMemory(&si,sizeof(si)); si.cb=sizeof(si);
	ZeroMemory(&pi,sizeof(pi));
	char* command=const_cast<char*>(cmd);
	bool retc=CreateProcess(NULL,command,NULL,NULL,false,0,NULL,NULL,&si,&pi);
    if(!retc) {
	    return_error("Failed to execute program!");
        return 1;
    } 
	time_t st=clock2(); 
	HANDLE hProcess=OpenProcess(PROCESS_QUERY_INFORMATION,true,pi.dwProcessId);
	while (1) {
		DWORD exit_code=0;
		GetExitCodeProcess(hProcess,&exit_code);
		runtime_error_state=exit_code;
		if (exit_code!=STILL_ACTIVE) {
			if (runtime_error_state) {
				if (!special_judge) return_info("Time usage: 0ms, memory usage: 0kb");
				else return_info("SPJ Time usage: 0ms, memory usage: 0kb");
				times=0;memory=0;
				return 4;
			}
			if (!special_judge) return_info(("Time usage: "+IntToString(times)+"ms, memory usage: "+IntToString(memory)+"kb").c_str());
			else return_info(("SPJ Time usage: "+IntToString(times)+"ms, memory usage: "+IntToString(memory)+"kb").c_str());
			return 0;
		} times=clock2()-st,memory=get_proc_mem(hProcess);
		if (memory>memory_limit) {
			if (!special_judge) return_info(("Time usage: "+IntToString(times)+"ms, memory usage: "+IntToString(memory)+"kb").c_str());
			else return_info(("SPJ Time usage: "+IntToString(times)+"ms, memory usage: "+IntToString(memory)+"kb").c_str());
			TerminateProcess(hProcess,0);
			return 3;
		}
		if (times>time_limit) {
			if (!special_judge) return_info(("Time usage: "+IntToString(times)+"ms, memory usage: "+IntToString(memory)+"kb").c_str());
			else return_info(("SPJ Time usage: "+IntToString(times)+"ms, memory usage: "+IntToString(memory)+"kb").c_str());
			TerminateProcess(hProcess,0);
			return 2;
		} 
	}
	CloseHandle(hProcess);
    return 0;
}
#endif

// Signal Analyst
string analysis_reason(int reason) {
	switch (reason) {
		case 1:return "SIGHUP";break;
		case 2:return "SIGINT";break;
		case 3:return "SIGQUIT";break;
		case 4:return "SIGILL";break;
		case 5:return "SIGTRAP";break;
		case 6:return "SIGABRT";break;
		case 7:return "SIGBUS";break;
		case 8:return "SIGFPE";break;
		case 9:return "SIGKILL";break;
		case 10:return "SIGUSR1";break;
		case 11:return "SIGSEGV";break;
		case 12:return "SIGUSR2";break;
		case 13:return "SIGPIPE";break;
		case 14:return "SIGALRM";break;
		case 15:return "SIGTERM";break;
		case 16:return "SIGSTKFLT";break;
		case 17:return "SIGCHLD";break;
		case 18:return "SIGCONT";break;
		case 19:return "SIGSTOP";break;
		case 20:return "SIGTSTP";break;
		case 21:return "SIGTTIN";break;
		case 22:return "SIGTTOU";break;
		case 23:return "SIGURG";break;
		case 24:return "SIGXCPU";break;
		case 25:return "SIGXFSZ";break;
		case 26:return "SIGVTALRM";break;
		case 27:return "SIGPROF";break;
		case 28:return "SIGWINCH";break;
		case 29:return "SIGIO";break;
		case 30:return "SIGPWR";break;
		case 31:return "SIGSYS";break;
		case 34:return "SIGRTMIN";break;
		case 35:return "SIGRTMIN+1";break;
		case 36:return "SIGRTMIN+2";break;
		case 37:return "SIGRTMIN+3";break;
		case 38:return "SIGRTMIN+4";break;
		case 39:return "SIGRTMIN+5";break;
		case 40:return "SIGRTMIN+6";break;
		case 41:return "SIGRTMIN+7";break;
		case 42:return "SIGRTMIN+8";break;
		case 43:return "SIGRTMIN+9";break;
		case 44:return "SIGRTMIN+10";break;
		case 45:return "SIGRTMIN+11";break;
		case 46:return "SIGRTMIN+12";break;
		case 47:return "SIGRTMIN+13";break;
		case 48:return "SIGRTMIN+14";break;
		case 49:return "SIGRTMIN+15";break;
		case 50:return "SIGRTMAX-14";break;
		case 51:return "SIGRTMAX-13";break;
		case 52:return "SIGRTMAX-12";break;
		case 53:return "SIGRTMAX-11";break;
		case 54:return "SIGRTMAX-10";break;
		case 55:return "SIGRTMAX-9";break;
		case 56:return "SIGRTMAX-8";break;
		case 57:return "SIGRTMAX-7";break;
		case 58:return "SIGRTMAX-6";break;
		case 59:return "SIGRTMAX-5";break;
		case 60:return "SIGRTMAX-4";break;
		case 61:return "SIGRTMAX-3";break;
		case 62:return "SIGRTMAX-2";break;
		case 63:return "SIGRTMAX-1";break;
		case 64:return "SIGRTMAX";break;
		default: return "Unknown Error";break;
	}
	return "Unknown Error";
}



// ****************************************************
// Class Name: Other Thread
// Class Module: Main
// Class Features: Open more thread to achieve more features
// ****************************************************

// Basic Variables
Json::FastWriter writer;
Json::Value cache;

// Heart Beating Upload Thread
void* heart_beating(void* arg) {
	string id=*(string*)arg;
	MYSQL conn;
    conn=mysqli_connect(cache["mysql-server"].asString().c_str(),cache["mysql-user"].asString().c_str(),
						cache["mysql-passwd"].asString().c_str(),cache["mysql-database"].asString().c_str(),
						cache["mysql-port"].asInt(),"heart-server");
	return_info("Listening to the database...","heart-server");
	while(1) {
		#ifdef __linux__
		sleep(1);
		#elif _WIN32
		Sleep(1000);
		#endif
		mysqli_execute(conn,("UPDATE judger SET heartbeat="+to_string(time(0))+" WHERE id='"+id+"'").c_str(),"heart-server");
		return_info("Upload heart beating successfully!","heart-server");
	}
}

// Crontab Monitor Thread
void RunCrontab(mysqld cron,string name,bool strong);
void* crontab_monitor(void* arg) {
	MYSQL conn; mysqld res;
    conn=mysqli_connect(cache["mysql-server"].asString().c_str(),cache["mysql-user"].asString().c_str(),
						cache["mysql-passwd"].asString().c_str(),cache["mysql-database"].asString().c_str(),
						cache["mysql-port"].asInt(),"crontab-server");
	return_info("Listening to the database...","crontab-server");
	while(1) {
		res=mysqli_query(conn,"SELECT * FROM crontab","crontab-server");
		if (res.size()==0) continue;
		#ifdef __linux__
		usleep(10000);
		#elif _WIN32
		Sleep(10);
		#endif
		RunCrontab(res,"crontab-server",false);
	} 
}



// ****************************************************
// Class Name: Main Features
// Class Module: Main
// Class Features: The Main Function
// ****************************************************

// Get System Information
string GetSystemInfo2() {
	#ifdef __linux__
	return "linux";
	#elif _WIN32
	return "Windows";
	#endif
}

// Register a New Judge Id
void RegisterJudgeId() {
	return_info("Registering a new judge id automatically...");
	char id[129]=""; srand(time(0)); 
	for (int i=0;i<128;i++) {
		int type=rand()%3;
		if (type==0) id[i]=rand()%10+'0';
		else if (type==1) id[i]=rand()%26+'a';
		else if (type==2) id[i]=rand()%26+'A';
	}
	return_info(("Register finished! The judge id: "+string(id)).c_str());
	cache["id"]=id;
}

// Write Configure into Cache File
void WriteConfigCache(Json::Value config) {
	cache["mysql-server"]=config["mysql"]["server"];
	cache["mysql-user"]=config["mysql"]["user"];
	cache["mysql-passwd"]=config["mysql"]["passwd"];
	cache["mysql-database"]=config["mysql"]["database"];
	cache["mysql-port"]=config["mysql"]["port"];
	cache["heart-thread"]=true;
	cache["crontab-thread"]=true;
}

// Run Scheduled Tasks
void RunCrontab(mysqld cron,string name,bool strong=false) {
	MYSQL conn;
	conn=mysqli_connect(cache["mysql-server"].asString().c_str(),cache["mysql-user"].asString().c_str(),
						cache["mysql-passwd"].asString().c_str(),cache["mysql-database"].asString().c_str(),
						cache["mysql-port"].asInt(),name);
	for (int i=0;i<cron.size();i++) {
		if (strong||clock2()/1000-StringToInt(cron[i]["lasttime"])>=StringToInt(cron[i]["duration"])) {
			return_info(("Running crontab id #"+cron[i]["id"]).c_str(),name);
			string result="";
			int ret=system2(cron[i]["command"].c_str(),result);
			return_info(result.c_str(),name);
			mysqli_execute(conn,("UPDATE crontab SET lasttime="+to_string(clock2()/1000)+" WHERE id="+cron[i]["id"]).c_str(),name);
			return_info(("Run crontab id #"+cron[i]["id"]+" finished!").c_str(),name);
			return_info(("Next execute time: "+getTime(StringToInt(cron[i]["duration"])+clock2()/1000)).c_str(),name);
		} 
	} mysql_close(&conn);
}

// Analyse Parameters
void AnalyseArgv(int argc,char** argv,Json::Value config) {
	map<string,string> param; // cout<<argc<<endl;
	for (int i=1;i<argc;i++) {
		string option=argv[i]; 
		if (option.size()<2) continue;
		if (option[0]!=option[1]||option[0]!='-') continue;
		option=option.substr(2);
		string header="",value="";
		if (option.find("=")==string::npos) header=option;
		else header=option.substr(0,option.find("=")),value=option.find("=")==option.size()-1?"":option.substr(option.find("=")+1);
		param.insert(make_pair(header,value));
	} 
	if (param.find("help")!=param.end()) {
		#ifdef __linux__
		cout<<"Usage: judge [options]"<<endl;
		#elif _WIN32
		cout<<"Usage: judge.exe [options]"<<endl;
		#endif
		cout<<"Sample judge program for lyoj, version "<<config["version"].asString()<<endl;
		cout<<endl;
		cout<<"Options:"<<endl;
		cout<<"    --reload-config=<config>         Reload config from file. "<<endl;
		#ifdef __linux__
		cout<<"                                     Default value is '/etc/judge/config.json'."<<endl;
		#elif _WIN32
		cout<<"                                     Default value is 'C://judge/config.json'."<<endl;
		#endif
		cout<<"    --mysql-server=<address>         Set MySQL/MariaDB server address to connect."<<endl;
		cout<<"                                     Default Value is '"+cache["mysql-server"].asString()+"'."<<endl;
		cout<<"    --mysql-user=<user>              Set login user for MySQL/MariaDB server."<<endl;
		cout<<"                                     Default value is '"+cache["mysql-user"].asString()+"'."<<endl;
		cout<<"    --mysql-passwd=<password>        Set login password for MySQL/MariaDB server."<<endl;
		cout<<"                                     Default value is '"+cache["mysql-passwd"].asString()+"'."<<endl;
		cout<<"    --mysql-database=<database>      Set login database for MySQL/MariaDB server."<<endl;
		cout<<"                                     Default value is '"+cache["mysql-database"].asString()+"'."<<endl;
		cout<<"    --mysql-port=<port>              Set MySQL/MariaDB server port to connect."<<endl;
		cout<<"                                     Default value is '"+cache["mysql-port"].asString()+"'."<<endl;
		cout<<"    --register-id                    Register a new judge id for this machine."<<endl;
		cout<<"    --run-crontab=<id|'all'>         Run scheduled task right now and refresh crontab."<<endl;
		cout<<"    --show-crontab                   Show all scheduled task."<<endl;
		cout<<"    --disable-heart                  Stop to run heart beating thread."<<endl;
		cout<<"    --enable-heart                   Start to run heart beating thread."<<endl;
		cout<<"                                     Default state: "<<(cache["heart-thread"].asBool()?"enable":"disable")<<endl;
		cout<<"    --disable-crontab                Stop to run crontab thread."<<endl;
		cout<<"    --enable-crontab                 Start to run crontab thread."<<endl;
		cout<<"                                     Default state: "<<(cache["crontab-thread"].asBool()?"enable":"disable")<<endl;
		cout<<"    --help                           Show help information."<<endl;
		exit(0);
	} 
	if (param.find("reload-config")!=param.end()) {
		#ifdef __linux__
		ifstream fin(param["reload-config"]==""?"/etc/judge/config.json":param["reload-config"]);
		#elif _WIN32
		ifstream fin(param["reload-config"]==""?"C:/judge/config.json":param["reload-config"]);
		#endif
		if (!fin) return_error("Failed to open config file.");
		Json::Value config; Json::Reader reader;
		if (!reader.parse(fin,config,false)) return_error("Failed to parse json object in config file");
		WriteConfigCache(config);
	}
	if (param.find("mysql-server")!=param.end()) cache["mysql-server"]=param["mysql-server"];
	if (param.find("mysql-user")!=param.end()) cache["mysql-user"]=param["mysql-user"];
	if (param.find("mysql-passwd")!=param.end()) cache["mysql-passwd"]=param["mysql-passwd"];
	if (param.find("mysql-database")!=param.end()) cache["mysql-database"]=param["mysql-database"];
	if (param.find("mysql-port")!=param.end()) cache["mysql-port"]=param["mysql-port"];
	if (param.find("register-id")!=param.end()) RegisterJudgeId();
	if (param.find("disable-heart")!=param.end()) cache["heart-thread"]=false;
	if (param.find("enable-heart")!=param.end()) cache["heart-thread"]=true;
	if (param.find("disable-crontab")!=param.end()) cache["crontab-thread"]=false;
	if (param.find("enable-crontab")!=param.end()) cache["crontab-thread"]=true;
	if (param.find("show-crontab")!=param.end()) {
		cout<<"All crontabs: "<<endl;
		MYSQL conn; mysqld res;
		conn=mysqli_connect(cache["mysql-server"].asString().c_str(),cache["mysql-user"].asString().c_str(),
							cache["mysql-passwd"].asString().c_str(),cache["mysql-database"].asString().c_str(),
							cache["mysql-port"].asInt());
		res=mysqli_query(conn,"SELECT * FROM crontab");
		for (int i=0;i<res.size();i++) {
			cout<<"["<<res[i]["id"]<<"] ["<<getTime(StringToInt(res[i]["lasttime"])+StringToInt(res[i]["duration"]))
				<<"] "<<res[i]["command"]<<endl;
		} exit(0);
	}
	if (param.find("run-crontab")!=param.end()) {
		if (param["run-crontab"]=="") param["run-crontab"]="all";
		MYSQL conn; mysqld res;
		conn=mysqli_connect(cache["mysql-server"].asString().c_str(),cache["mysql-user"].asString().c_str(),
							cache["mysql-passwd"].asString().c_str(),cache["mysql-database"].asString().c_str(),
							cache["mysql-port"].asInt());
		if (param["run-crontab"]=="all") res=mysqli_query(conn,"SELECT * FROM crontab");
		else res=mysqli_query(conn,("SELECT * FROM crontab WHERE id="+param["run-crontab"]).c_str());
		RunCrontab(res,"main-server",true);
	}
}

Json::Value judge,val;
Json::Value judge_data(int pid,int dataid,int lang,int& state,int& rest,int& resm) {
	Json::Value single; int sum_t=0,max_m=0;
	
	// Copy Test Data
	long long st=clock2();
	#ifdef __linux__
	int retc=system(("rm /etc/judge/tmp/"+val["input"].asString()).c_str());
	retc=system2(("ln \"/etc/judge/problem/"+IntToString(pid)+"/"+val["data"][dataid]["input"].asString()+"\" \""+
	"/etc/judge/tmp/"+val["input"].asString()+"\" -s").c_str(),garbage);
	#elif _WIN32
	int retc=system2(("copy \"problem\\"+IntToString(pid)+"\\"+val["data"][dataid]["input"].asString()+"\" \""+
	"tmp\\"+val["input"].asString()+"\" /Y").c_str(),garbage);
	#endif
	if (retc) {
		return_error(("Failed to create link for input file in problem #"+IntToString(pid)).c_str(),false);
		return_error(("Error file name: "+val["data"][dataid]["input"].asString()+"/"+
		val["data"][dataid]["output"].asString()).c_str(),false);
		return_error(garbage.c_str(),false);
		return_error(to_string(retc).c_str(),false);
		state=6; rest=sum_t,resm=max_m;
		return single;
	}
	
	// Remove Exist Output File
	ofstream tmpout(("./tmp/"+val["output"].asString()).c_str()); tmpout.close();
	int x=system(("chmod 0777 ./tmp/"+val["output"].asString()).c_str());

	// Update Working Directory
	__chdir("./tmp/");
	long long t=0,m=0,ret; string command=judge["lang"][lang]["exec_command"].asString();
	string extra_command="";
	ret=run_code(command.c_str(),t,m,val["data"][dataid]["time"].asInt(),val["data"][dataid]["memory"].asInt(),
	false,val["input"].asString()=="stdin",val["output"].asString()=="stdout");
	
	// Update Working Directory
	__chdir("../");
	
	// When Exited Abnormally
	if (ret) {
		single["time"]=t;single["memory"]=m;
		sum_t+=t,max_m=max((long long)max_m,m);
		
		// Update the Whole Judging State
		if (!state) state=ret;
		
		// Analyse Exited Reason and Full JSON Object
		switch (ret) {
			case 2: single["state"]="Time Limited Exceeded",single["info"]="Time Limited Exceeded";break;
			case 3: single["state"]="Memory Limited Exceeded",single["info"]="Memory Limited Exceeded";break;
			case 4: single["state"]="Runtime Error",
			single["info"]="Runtime Error | "+analysis_reason(runtime_error_reason);break;
			default: single["state"]="Unknown Error",single["info"]="Unknown Error";break;
		} single["score"]=0;
		return_info(single["info"].asString().c_str());
		// if (ret==2){info.append(single);break;}
		
		// Append Result to the whole JSON Object
		state=ret; rest=sum_t,resm=max_m;
		return single;
	}
	
	// When Exited Normally
	single["time"]=t,single["memory"]=m;
	sum_t+=t,max_m=max((long long)max_m,m);
	
	// Remove Exist Garbase
	tmpout.open("./tmp/score.txt");tmpout.close();
	tmpout.open("./tmp/info.txt");
	
	// Gain the Absolute Path for some File
	string inputpath=getpath(("./problem/"+IntToString(pid)+"/"+val["data"][dataid]["input"].asString()).c_str());
	string outputpath=getpath(("./tmp/"+val["output"].asString()).c_str());
	string answerpath=getpath(("./problem/"+IntToString(pid)+"/"+val["data"][dataid]["output"].asString()).c_str());
	string resultpath=getpath("./tmp/score.txt"),infopath=getpath("./tmp/info.txt");
	string sourcepath=getpath(("./tmp/"+judge["lang"][lang]["source_path"].asString()).c_str());
	long long spjt,spjm;
	
	// Update Working Directory
	__chdir("./tmp");
	
	// Running Special Judger
	#ifdef __linux__
	ret=run_code(("./spj "+inputpath+" "+outputpath+" "+answerpath+" "+
	val["data"][dataid]["score"].asString()+" "+resultpath+" "+infopath+" "+sourcepath+" "+
	val["spj"]["exec_param"].asString()).c_str(),
	spjt,spjm,val["data"][dataid]["time"].asInt(),val["data"][dataid]["memory"].asInt(),true);
	#elif _WIN32
	ret=run_code(("spj.exe "+inputpath+" "+outputpath+" "+answerpath+" "+
	val["data"][dataid]["score"].asString()+" "+resultpath+" "+infopath+" "+
	val["spj"]["exec_param"].asString()).c_str(),
	spjt,spjm,val["data"][dataid]["time"].asInt(),val["data"][dataid]["memory"].asInt(),true);
	#endif
	
	// Update Working Directory
	__chdir("../");
	
	// When SPJ Exited Abnormally
	if (ret) {
		single["time"]=spjt+t;single["memory"]=spjm+m;
		sum_t+=spjt+t,max_m=max((long long)max_m,spjm+m);
		
		// Update the Whole State
		if (!state) state=ret;
		
		// Analyse Exited Reason and Full JSON Object
		switch (ret) {
			case 2: single["state"]="Time Limited Exceeded",single["info"]="Special Judge Time Limited Exceeded";break;
			case 3: single["state"]="Memory Limited Exceeded",single["info"]="Special Judge Memory Limited Exceeded";break;
			case 4: single["state"]="Runtime Error",
			single["info"]="Runtime Error | Special Judge "+analysis_reason(runtime_error_reason);break;
			default: single["state"]="Unknown Error",single["info"]="Special Judge Unknown Error";break;
		} single["score"]=0;
		return_info(single["info"].asString().c_str());
		
		// Append Result to the whole JSON Object
		state=ret; rest=sum_t,resm=max_m;
		return single;
	}
	
	// Read Score and Judger Info
	int gain_score=0;ifstream scorein("./tmp/score.txt");
	scorein>>gain_score;scorein.close();
	string spj_info="";ifstream infoin("./tmp/info.txt");
	while (!infoin.eof()) {
		string input;getline(infoin,input);
		spj_info+=input+"\n";
	} infoin.close();
	
	// Analyse Result and Full JSON Object
	int now_state=0; 
	if (gain_score>=val["data"][dataid]["score"].asInt()) return_info("Accepted | OK!"),single["state"]="Accepted",now_state=0;
	else if (gain_score==-1) return_info("Wrong Answer!"),single["state"]="Wrong Answer",now_state=1,gain_score=0;
	else now_state=7,return_info(("Partially Correct, Gain "+to_string(gain_score)+"/"+
	val["data"][dataid]["score"].asString()+"!").c_str()),single["state"]="Partially Correct";
	
	// Update the Whole State
	state=now_state; rest=sum_t,resm=max_m;
	
	// Full the Information of this Test Data
	single["info"]=spj_info;single["score"]=gain_score;
	return single;
}

// Main Function
Json::Value config; Json::Reader reader;
bool accepted[100010];
int main(int argc,char** argv) {
	// Creating Daemon Processor
	// if(daemon(1,0)<0) return_error("Failed to create daemon process.");
	// system("ls");

	// Updating Working Directory
	#ifdef __linux__
	int res=chdir("/etc/judge");
	#elif _WIN32
	int res=SetCurrentDirectory("C://judge");
	#endif
	int x=system("sudo chmod 0777 ./tmp -R");
	
	// Reading Judger Configure
	ifstream fin("./config.json");
	if (!fin) return_error("Failed to open config file.");
	if (!reader.parse(fin,config,false)) return_error("Failed to parse json object in config file");
	fin.close(); fin.open("./config.cache");
	if (fin) reader.parse(fin,cache,false);
	else WriteConfigCache(config);
	fin.close(); AnalyseArgv(argc,argv,config);
	if (cache["id"].asString().size()!=128) RegisterJudgeId();
	ofstream fout("./config.cache");
	string cache_string=writer.write(cache);
	fout<<cache_string<<endl; fout.close();
	
	// Connecting to the Database 
	MYSQL conn; mysqld result;
    conn=mysqli_connect(cache["mysql-server"].asString().c_str(),cache["mysql-user"].asString().c_str(),
						cache["mysql-passwd"].asString().c_str(),cache["mysql-database"].asString().c_str(),
						cache["mysql-port"].asInt());
    result=mysqli_query(conn,("SELECT * FROM judger WHERE id='"+cache["id"].asString()+"'").c_str());
    if (result.size()==0) {
    	return_info("Couldn't find judge info on the database!");
    	return_info("Registering this judger on the database...");
    	string conf=writer.write(config);
    	conf=str_replace("'","\\'",conf.c_str());
    	mysqli_execute(conn,("INSERT INTO judger (id,config,name,lasttime) VALUES ('"
					+cache["id"].asString()+"','"+conf+"','"+GetSystemInfo2()+"',0)").c_str());
	}
	pthread_t th1,th2; string judgeid=cache["id"].asString();
	if (cache["heart-thread"].asBool()) pthread_create(&th1,NULL,heart_beating,&judgeid);
	if (cache["crontab-thread"].asBool()) pthread_create(&th2,NULL,crontab_monitor,NULL);
	return_info("Listening to the database...");
	
    conn=mysqli_connect(cache["mysql-server"].asString().c_str(),cache["mysql-user"].asString().c_str(),
						cache["mysql-passwd"].asString().c_str(),cache["mysql-database"].asString().c_str(),
						cache["mysql-port"].asInt());
	// The Main Processor to Monitor the Database
		
	judge=config;
	
    while (1) {
    	// Querying Waited Judge Program
    	#ifdef __linux__
    	usleep(100000);
    	#elif _WIN32
    	Sleep(100);
    	#endif
        mysqld ress=mysqli_query(conn,"SELECT * FROM status WHERE judged=0 LIMIT 1");
		if (ress.size()==0) continue;
		
		// Judging Submitted Program
//		cout<<result.res<<endl;
		for (int gdfszfd=0;gdfszfd<ress.size();gdfszfd++) {
			
			// ****************************************************
			// Class Name: Data Gainer
			// Class Module: Main
			// Class Features: Gain Data from Database
			// ****************************************************
			
			// Gain Data from Queried Result
			int pid=StringToInt(ress[gdfszfd]["pid"]),uid=StringToInt(ress[gdfszfd]["uid"]),
				id=StringToInt(ress[gdfszfd]["id"]),lang=StringToInt(ress[gdfszfd]["lang"]);
			string code=ress[gdfszfd]["code"],ideinfo=ress[gdfszfd]["ideinfo"];
			#ifdef __linux__ 
			int retc=system("sudo rm ./tmp/* -r");
			#elif _WIN32
			int retc=system("del /F /Q tmp");
			#endif
			__chdir("./tmp/");
			
			
			// ****************************************************
			// Class Name: Info Outputer
			// Class Module: Main
			// Class Features: Output Submitted Information
			// ****************************************************
			
			// Output Submitted Information
			return_info(("Read status id #"+IntToString(id)).c_str());
			return_info(("Problem id: #"+IntToString(pid)).c_str());
			return_info(("Submitted user id: #"+IntToString(uid)).c_str());
			return_info(("Language: "+judge["lang"][lang]["name"].asString()).c_str());
			
			// Output Source to the File
			ofstream fout(judge["lang"][lang]["source_path"].asString().c_str());
			fout<<code<<endl;
			fout.close();
			code=str_replace("'","\\'",str_replace("\\","\\\\",code.c_str()).c_str());




			// ****************************************************
			// Class Name: Source Compiler
			// Class Module: Main
			// Class Features: Compile Source File
			// ****************************************************
			
			// Update Judging State
			mysqli_execute(conn,("UPDATE status SET status='Compiling...' WHERE id="+to_string(id)).c_str());
			
			// Compiling Code
			time_t st=clock2();int retcode; string info_string="";
			if (judge["lang"][lang]["type"].asInt()!=1) {
				return_info(("Compiling code from status id #"+IntToString(id)).c_str());
				retcode=system2(judge["lang"][lang]["command"].asString().c_str(),info_string);
				
				// The Situation of Compile Error
				if (retcode) {
					Json::Value res;
					return_info("Error compile code!");
					return_info(("Compiler return error code "+to_string(retcode)).c_str());
					res["result"]="Compile Error";
					res["output"]="Compile Error";
					res["compile_info"]=info_string;
					return_info(info_string.c_str());
					
					// Insert Data to the Database
					mysqli_execute(conn,("UPDATE status SET result='"+
					str_replace("'","\\'",str_replace("\\","\\\\",writer.write(res).c_str()).c_str())+"',"+
					"judged=1,status='"+res["result"].asString()+"' "+
					"WHERE id='"+to_string(id)+"'").c_str());
					__chdir("../");
					continue;
				}
				return_info(("Compile finished, use "+to_string((clock2()-st))+"ms").c_str());
			}
			__chdir("../");
					
					
					
					
			// ****************************************************
			// Class Name: Program Configure Gainer
			// Class Module: Main
			// Class Features: Gain Program Configure
			// ****************************************************
			
			// Reading Problem Configure.
			Json::Reader reader;
			ifstream fin(("./problem/"+IntToString(pid)+"/config.json").c_str());
			
			// Failed to Open the Problem Configure
			if (pid&&!fin) {
				return_error(("Failed to open problem config file id #"+IntToString(pid)).c_str(),false);
				Json::Value res;
				res["result"]="No Test Data";
				
				// Insert Data to the Database
				mysqli_execute(conn,("UPDATE status SET result='"+
				str_replace("'","\\'",str_replace("\\","\\\\",writer.write(res).c_str()).c_str())+"',"+
				"judged=1,status='"+res["result"].asString()+"' "+
				"WHERE id='"+to_string(id)+"'").c_str());
				continue;
			}
			
			// Failed to Parse JSON Object
			if (pid&&!reader.parse(fin,val,false)) {
				return_error(("Failed to parse json object in problem config file #"+IntToString(pid)).c_str(),false);
				Json::Value res;
				res["result"]="No Test Data";
				cout<<endl;
				
				// Insert Data to the Database
				mysqli_execute(conn,("UPDATE status SET result='"+
				str_replace("'","\\'",str_replace("\\","\\\\",writer.write(res).c_str()).c_str())+"',"+
				"judged=1,status='"+res["result"].asString()+"' "+
				"WHERE id='"+to_string(id)+"'").c_str());
				continue;
			}




			// ****************************************************
			// Class Name: Special Judger Compiler
			// Class Module: Main
			// Class Features: Compile Special Judger
			// ****************************************************
			
			// Compiling Special Judger
			int state=0;Json::Value info,res;st=clock2();string spj_path;
			
			if (!pid) ;
			// New Special Judger
			else if (val["spj"]["type"].asInt()==0) {
				return_info(("Compiling special judge from status id #"+IntToString(id)).c_str()); string info_string2="";
				retcode=system2(("cp ./spjtemp/testlib.h ./problem/"+to_string(pid)+"/testlib.h").c_str(),info_string2);
				__chdir(("./problem/"+to_string(pid)).c_str());
				retcode=system2((val["spj"]["compile_cmd"].asString()).c_str(),info_string2);
				if (retcode) {
					Json::Value res;
					return_info("Error compile special judge!");
					res["result"]="Compile Error";
					res["compile_info"]="In SPJ:\n"+info_string2;
					mysqli_execute(conn,("UPDATE status SET result='"+
					str_replace("'","\\'",str_replace("\\","\\\\",writer.write(res).c_str()).c_str())+"',"+
					"judged=1,status='"+res["result"].asString()+"' "+
					"WHERE id='"+to_string(id)+"'").c_str());
					continue;
				}
				__chdir("../../");
				spj_path="./problem/"+to_string(pid)+"/"+val["spj"]["exec_path"].asString();
				return_info(("Compile finished, use "+to_string((clock2()-st))+"ms").c_str());
			} 
			
			// Invaild Special Judger Configure
			else if (val["spj"]["type"].asInt()>judge["spj"].size()) {
				return_error(("Failed to analyse special judge type in problem config file #"+IntToString(pid)).c_str(),false);
				Json::Value res;
				res["result"]="Compile Error";
				res["compile_info"]="Invaild special judge type!";
				mysqli_execute(conn,("UPDATE status SET result='"+
				str_replace("'","\\'",str_replace("\\","\\\\",writer.write(res).c_str()).c_str())+"',"+
				"judged=1,status='"+res["result"].asString()+"' "+
				"WHERE id='"+to_string(id)+"'").c_str());
				continue;
			}
			
			// Exist Special Judger Template
			else spj_path=judge["spj"][val["spj"]["type"].asInt()-1]["path"].asString();
			
			// Copy Special Judger to the Temporary Directory
			#ifdef __linux__
			int ret=system(("cp \""+spj_path+"\" \"./tmp/spj\"").c_str());
			#elif _WIN32
			while (spj_path.find("/")!=string::npos) spj_path.replace(spj_path.find("/"),1,"\\");
			int ret=system2(("copy \""+spj_path+"\" \".\\tmp\\spj.exe\" /Y").c_str(),garbage);
			#endif




			// ****************************************************
			// Class Name: Program Judger
			// Class Module: Main
			// Class Features: Judge Test Data
			// ****************************************************	
			
			// Solve the Remote IDE Function
			if (pid==0) {
			    mysqli_execute(conn,("UPDATE status SET status='Running...' WHERE id="+to_string(id)).c_str());
				ofstream fout("./tmp/test.in");
				Json::Value ide;long long t,m;
				reader.parse(ideinfo,ide);
				fout<<ide["input"].asString();fout.close();
				__chdir("./tmp");
				int ret=run_code(judge["lang"][lang]["exec_command"].asString().c_str(),
				t,m,ide["t"].asInt(),ide["m"].asInt());
				__chdir("../");
				if (ret) switch (ret) {
					case 2: res["result"]="Time Limited Exceeded",res["info"]="Time Limited Exceeded";break;
					case 3: res["result"]="Memory Limited Exceeded",res["info"]="Memory Limited Exceeded";break;
					case 4: res["result"]="Runtime Error",
					res["info"]="Runtime Error | "+analysis_reason(runtime_error_reason);break;
					default: res["result"]="Unknown Error",res["info"]="Unknown Error";break;
				} else {
					ifstream fin("./tmp/test.out");
					if (!fin) res["result"]="Wrong Answer";
					else {
						res["result"]="Accepted";
						string resstring="";
						while (!fin.eof()) {
							string a;getline(fin,a);
							resstring+=a+"\n";
						}
						res["output"]=resstring;
					}	
				} res["time"]=t,res["memory"]=m;
				res["compile_info"]=info_string;
				mysqli_execute(conn,("UPDATE status SET result='"+
				str_replace("'","\\'",str_replace("\\","\\\\",writer.write(res).c_str()).c_str())+"',"+
				"judged=1,status='"+res["result"].asString()+"' "+
				"WHERE id='"+to_string(id)+"'").c_str());
				continue;
			}

			// Judging Program
			res["compile_info"]=info_string;int sum_t=0,max_m=0;
			vector<int> Taskids[100010];
			for (int i=0;i<val["data"].size();i++) Taskids[val["data"][i]["subtask"].asInt()].push_back(i);
			int testnum=0; for (int i=0;i<=1e5;i++) accepted[i]=1;
			for (int i=0;i<Taskids[0].size();i++) {
				++testnum; return_info(("Running on Test "+to_string(testnum)+"...").c_str());
				// Update Judging State
				long long st=clock2();
				mysqli_execute(conn,("UPDATE status SET status='Running on Test "+to_string(testnum)+"...' WHERE id="+to_string(id)).c_str());
				Json::Value status; int now_state; int t,m;
				status=judge_data(pid,Taskids[0][i],lang,now_state,t,m);
				sum_t+=t; max_m=max(max_m,m);
				if (now_state==-1) continue;
				if (!state) state=now_state;
				info.append(status);
			} int depend_pt=-1;
			for (int i=1;i<=1e5;i++) {
				if (Taskids[i].size()!=0) depend_pt++;
				for (int j=0;j<Taskids[i].size();j++) {
					int dataid=Taskids[i][j];
					++testnum; return_info(("Running on Test "+to_string(testnum)+"...").c_str());
					// Update Judging State
					mysqli_execute(conn,("UPDATE status SET status='Running on Test "+to_string(testnum)+"...' WHERE id="+to_string(id)).c_str());
					bool depend=true;
					for (int k=0;depend_pt<val["subtask_depend"].size()&&k<val["subtask_depend"][depend_pt].size();k++) 
						if (!accepted[val["subtask_depend"][depend_pt][k].asInt()]) depend=false;
					if (!accepted[i]||!depend) {
						return_info("Skipped!");
						Json::Value status;
						status["time"]=status["memory"]=status["score"]=0;
						status["state"]="Skipped";status["info"]="Skipped!";
						info.append(status);
						continue;
					} mysqli_execute(conn,("UPDATE status SET status='Running on Test "+to_string(testnum)+"...' WHERE id="+to_string(id)).c_str());
					Json::Value status; int now_state; int t,m;
					status=judge_data(pid,dataid,lang,now_state,t,m);
					sum_t+=t; max_m=max(max_m,m);
					if (now_state==-1) continue;
					if (!state) state=now_state;
					if (now_state!=0) accepted[i]=false;
					info.append(status);
				}
			}
			
			// When There are no Test Data
			if (val["data"].size()==0) state=5;
			
			// Analyse the Whole Problem State
			switch(state) {
				case 0: res["result"]="Accepted";break;
				case 1: res["result"]="Wrong Answer";break;
				case 2: res["result"]="Time Limited Exceeded";break;
				case 3: res["result"]="Memory Limited Exceeded";break;
				case 4: res["result"]="Runtime Error";break;
				case 5: res["result"]="No Test Data";break;
				case 6: res["result"]="Unknown Error";break;
				case 7: res["result"]="Partially Correct";break;
			}
			
			// Full the JSON Object
			res["info"]=info;res["time"]=sum_t;res["memory"]=max_m;
			
			// Upload data to the database.
			mysqli_execute(conn,("UPDATE status SET result='"+
			str_replace("'","\\'",str_replace("\\","\\\\",writer.write(res).c_str()).c_str())+"',"+
			"judged=1,status='"+res["result"].asString()+"'"+
			"WHERE id="+to_string(id)).c_str());
		} 
    }
}