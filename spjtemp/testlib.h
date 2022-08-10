#ifndef _TESTLIB_H_
#define _TESTLIB_H_

#include<bits/stdc++.h>
using namespace std;

string message[]={
	"Program must be run with the following arguments: \
<input-file> <output-file> <answer-file> <score> <score-file> <info-file> [<code-file>]\n\
Use \"--help\" to get help information",
	"You cannot use this function before you initialize you environment!",
};
double EPS=1e-10;
enum TResult{
	_ok=0,
    _wa=1,
    _pe=2,
    _fail=3,
    _dirt=4,
    _points=5,
    _unexpected_eof=6
};

void outError(string info);
void outWarning(string info);
void quitf(int status,string,...);

long long string2int(string x) {
	long long res=0; 
	for (int i=0;i<x.size();i++) {
		res*=10,res+=x[i]-'0';
	} return res;
}
string int2string(long long x) {
	char ch[1024]=""; int k=-1;
	long long tmp=x; x=abs(x);
	if (x==0) return "0"; 
	while (x) ch[++k]=x%10+'0',x/=10;
	reverse(ch,ch+k+1);
	return (tmp<0?"-":"")+(string)ch;
}
string double2string(double x,int bit=4) {
	double tmp=x; x=abs(x);
	long long zh=x; x-=zh;
	char ch[1024]=""; int k=-1;
	if (zh==0) ch[++k]='0';
	while (zh) ch[++k]=zh%10+'0',zh/=10;
	reverse(ch,ch+k+1);
	ch[++k]='.';
	for (int i=1;i<=bit;i++) {
		x*=10; ch[++k]=(int)x+'0';
		x-=(int)x;
	} return (tmp<0?"-":"")+(string)ch;
}
void checkRange(long long L,long long R) {
	if (L>R) outError("Invalid range ["+int2string(L)+","+int2string(R)+"]!");
}
void checkRange2(double L,double R) {
	if (R-L<=EPS) outError("Invalid range ["+double2string(L)+","+double2string(R)+"]!");
}

struct FileStream {
	string filepath="";
	ifstream fin;
	bool opened=0;
	void init(string path);
	void checkOpen();
	bool checkEof();
	long long checkInt(string name);
	double checkDouble(string name);
	int readInt();
	int readInteger();
	int readInt(int L,int R,string name);
	int readInteger(int L,int R,string name);
	vector<int> readInts(int n,int L,int R,string name);
	vector<int> readIntegers(int n,int L,int R,string name);
	long long readLong();
	long long readLong(long long L,long long R,string name);
	vector<long long> readLongs(long long n,long long L,long long R,string name);
	double readDouble();
	double readReal();
	double readDouble(double L,double R,string name);
	double readReal(double L,double R,string name);
	char readChar();
	char readChar(char x);
	char readSpace();
	string readToken();
	string readWord();
	string readToken(string regex);
	string readWord(string regex);
	string readLine();
	string readString();
	string readLine(string regex);
	string readString(string regex);
	void readEoln();
	void readEof();
	bool seekEof();
	void quitf(int status,string format,...);
} inf,ouf,ans;

ofstream f1,f2; ifstream f3; string code;
int full_score=0;

void outError(string info){cout<<"[Error] "<<info<<endl;exit(3);}
void outWarning(string info){cout<<"[Warning] "<<info<<endl;}

void FileStream::init(string path) {
	this->fin.open(path);this->filepath=path;this->opened=1;
}
void FileStream::checkOpen() {
	if (!this->opened) outError(message[1]);
}
bool FileStream::checkEof() {
	return this->fin.eof();
}
long long FileStream::checkInt(string name="") {
	if (this->checkEof()) {
		if (name=="") quitf(_wa,"Expected an integer, but read eof!");
		else quitf(_wa,"Expected an integer '"+name+"', but read eof!");
	} string x; this->fin>>x; 
	if (x.size()==0) {
		if (name=="") quitf(_wa,"Expected an integer, but read eof!");
		else quitf(_wa,"Expected an integer '"+name+"', but read eof!");
	} long long res=0,st=0,f=1;
	if (x[0]=='-') {
		if (x.size()==1) {
			if (name=="") quitf(_wa,"Expected an integer, but read '"+x+"'!");
			else quitf(_wa,"Expected an integer '"+name+"', but read '"+x+"'!");
		} f=-1,st++;  
	} for (int i=st;i<x.size();i++) {
		if (!isdigit(x[i])) {
			if (name=="") quitf(_wa,"Expected an integer, but read '"+x+"'!");
			else quitf(_wa,"Expected an integer '"+name+"', but read '"+x+"'!");
		} else res*=10,res+=x[i]-'0';
	} return f*res;
}
double FileStream::checkDouble(string name="") {
	if (this->checkEof()) {
		if (name=="") quitf(_wa,"Expected a floating point number, but read eof!");
		else quitf(_wa,"Expected a floating point number '"+name+"', but read eof!");
	} string x; this->fin>>x;
	if (x.size()==0) {
		if (name=="") quitf(_wa,"Expected a floating point number, but read eof!");
		else quitf(_wa,"Expected a floating point number '"+name+"', but read eof!");
	} double res=0,f=1; int pt=0,st=0; double eps=1;
	if (x[0]=='-') {
		if (x.size()==1) {
			if (name=="") quitf(_wa,"Expected a floating point number, but read '"+x+"'!");
			else quitf(_wa,"Expected a floating point number '"+name+"', but read '"+x+"'!");
		} f=-1,st++;  
	} for (int i=st;i<x.size();i++) {
		if (isdigit(x[i])) continue;
		if (x[i]=='.') pt++;
		else if (name=="") quitf(_wa,"Expected a floating point number, but read '"+x+"'");
		else quitf(_wa,"Expected a floating point number '"+name+"', but read '"+x+"'");
	} if (pt>1) if (name=="") quitf(_wa,"Expected a floating point number, but read '"+x+"'");
	else quitf(_wa,"Expected a floating point number '"+name+"', but read '"+x+"'");
	bool mode=0; for (int i=st;i<x.size();i++) {
		if (x[i]=='.') {mode=1;continue;}
		if (!mode) res*=10,res+=x[i]-'0';
		else eps/=10,res+=(x[i]-'0')*eps;
	} return f*res;
}
int FileStream::readInt() {
	this->checkOpen();
	return this->checkInt();
}
int FileStream::readInteger() {
	return this->readInt();
}
int FileStream::readInt(int L,int R,string name="") {
	this->checkOpen(); checkRange(L,R);
	int x=this->checkInt(name);
	if (x<L||x>R) {
		if (name=="") quitf(_wa,"Expected an integer range ["+int2string(L)+
			","+int2string(R)+"], but read '"+int2string(x)+"'!"); 
		else quitf(_wa,"Expected an integer '"+name+"' range ["+int2string(L)+
			","+int2string(R)+"], but read '"+int2string(x)+"'!"); 
	}
	return x;
}
int FileStream::readInteger(int L,int R,string name="") {
	return this->readInt(L,R,name);
}
vector<int> FileStream::readInts(int n,int L,int R,string name="") {
	vector<int> res; 
	for (int i=1;i<=n;i++) res.push_back(this->readInt(L,R,name));
	return res;
}
vector<int> FileStream::readIntegers(int n,int L,int R,string name="") {
	return this->readInts(n,L,R,name);
}
long long FileStream::readLong() {
	this->checkOpen();
	return this->checkInt();
}
long long FileStream::readLong(long long L,long long R,string name="") {
	this->checkOpen(); checkRange(L,R);
	int x=this->checkInt(name);
	if (x<L||x>R) {
		if (name=="") quitf(_wa,"Expected an integer range ["+int2string(L)+
			","+int2string(R)+"], but read '"+int2string(x)+"'!"); 
		else quitf(_wa,"Expected an integer '"+name+"' range ["+int2string(L)+
			","+int2string(R)+"], but read '"+int2string(x)+"'!"); 
	}
	return x;
}
vector<long long> FileStream::readLongs(long long n,long long L,long long R,string name="") {
	vector<long long> res;
	for (int i=1;i<=n;i++) res.push_back(readLong(L,R,name));
	return res;
}
double FileStream::readDouble() {
	this->checkOpen();
	return this->checkDouble();
}
double FileStream::readReal() {
	return this->readDouble();
}
double FileStream::readDouble(double L,double R,string name="") {
	this->checkOpen(); checkRange2(L,R);
	double x=this->checkDouble(name);
	if (x-L<=EPS||R-x<=EPS) {
		if (name=="") quitf(_wa,"Expected a floating point number range ["+double2string(L)+
			","+double2string(R)+"], but read '"+double2string(x)+"'!");
		else quitf(_wa,"Expected a floating point number '"+name+"' range ["+double2string(L)+
			","+double2string(R)+"], but read '"+double2string(x)+"'!");
	} return x;
}
double FileStream::readReal(double L,double R,string name="") {
	return this->readDouble(L,R,name);
}
char FileStream::readChar() {
	this->checkOpen(); 
	if (this->checkEof()) quitf(_wa,"Expected a character, but read eof!");
	char x; this->fin>>x;
	return x;
}
char FileStream::readChar(char x) {
	this->checkOpen(); 
	if (this->checkEof()) quitf(_wa,"Expected a character, but read eof!");
	char res; this->fin>>res;
	if (res!=x) quitf(_wa,(string)"Expected a character '"+x+(string)"', but read '"+res+(string)"'");
	return res;
}
char FileStream::readSpace() {
	return this->readChar(' ');
}
string FileStream::readToken() {
	this->checkOpen(); 
	if (this->checkEof()) quitf(_wa,"Expected a string, but read eof!");
	string x; this->fin>>x;
	return x;
}
string FileStream::readWord() {
	return this->readToken();
}
string FileStream::readToken(string regex) {
	string x=this->readToken();
	if (x!=regex) quitf(_wa,"Expected a string equal to '"+regex+"', but read '"+x+"'!");
	return x;
}
string FileStream::readWord(string regex) {
	return this->readToken(regex);
}
string FileStream::readLine() {
	this->checkOpen();
	if (this->checkEof()) quitf(_wa,"Expected a line of string, but read eof!");
	string x; getline(this->fin,x);
	return x;
}
string FileStream::readString() {
	return this->readLine();
}
string FileStream::readLine(string regex) {
	string x=this->readLine();
	if (x!=regex) quitf(_wa,"Expected a line of string equal to '"+regex+"', but read '"+x+"'!");
	return x;
}
string FileStream::readString(string regex) {
	return this->readLine(regex);
}
void FileStream::readEoln() {
#if ( _WIN32 || __WIN32__ || _WIN64 || __WIN64__ )
	this->readChar('\r');
	this->readChar('\n');
#else 
	this->readChar('\n');
#endif
}
void FileStream::readEof() {
	if (!this->checkEof()) {
		string x; this->fin>>x;
		quitf(_wa,"Expected eof, but read '"+x+"'!");
	}
}
bool FileStream::seekEof() {
	return this->checkEof();
}
int _pc(int score) {
	return score+7;
}
void FileStream::quitf(int status,string format,...) {
	char sprint_buf[1024*1024];
	if (!f1.is_open()) outError(message[1]);
    int num=0; va_list va_l;
    va_start(va_l,format);
	switch(status) {
		case _ok: f1<<full_score;break;
		case _wa: f1<<-1;break;
		case _pe: f1<<-1;break;
		case _fail: f1<<-1;break;
		case _dirt: f1<<-1;break;
		case _points: f1<<-1;break;
		case _unexpected_eof: f1<<-1;break;
		default:
			int percent=status-7;
			f1<<full_score*percent/100;
			break;
	} vsprintf(sprint_buf,format.c_str(),va_l);
	f2<<sprint_buf;
	va_end(va_l);
	exit(0);
}
void quitf(int status,string format,...) {
	char sprint_buf[1024*1024];
	if (!f1.is_open()) outError(message[1]);
    int num=0; va_list va_l;
    va_start(va_l,format);
	switch(status) {
		case _ok: f1<<full_score;break;
		case _wa: f1<<-1;break;
		case _pe: f1<<-1;break;
		case _fail: f1<<-1;break;
		case _dirt: f1<<-1;break;
		case _points: f1<<-1;break;
		case _unexpected_eof: f1<<-1;break;
		default:
			int percent=status-7;
			f1<<full_score*percent/100;
			break;
	} vsprintf(sprint_buf,format.c_str(),va_l);
	f2<<sprint_buf;
	va_end(va_l);
	exit(0);
}

void registerTestlibCmd(int argc,char* argv[]) {
	if (argc<7) outError(message[0]);
	inf.init(argv[1]);
	ouf.init(argv[2]);
	ans.init(argv[3]);
	full_score=string2int(argv[4]);
	f1.open(argv[5]);
	f2.open(argv[6]);
	if (argc>7) f3.open(argv[7]);
}

#endif